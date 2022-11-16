<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Entity\News;

#[AsCommand(
    name: 'news:parse',
    description: 'Parse news from different countries.',
    hidden: false
)]
class NewsParserCommand extends Command
{
    private $httpclient;
    private $validator;
    
    //Name of the command
     protected static $defaultName = "app:check";

     //Description of the command
     protected static $defaultDescription = "Test command for me";

    /**
     * Constructor to initialize values
     */
    public function __construct(
        HttpClientInterface $httpClient,
        ValidatorInterface $validator,
        ManagerRegistry $dbMagager
    ){
        parent::__construct();

        $this->httpclient = $httpClient;
        $this->validator = $validator;
        $this->dbMagager = $dbMagager;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        
        
        $output->writeln([
            '======================================',
            '======== News Parsing Service ========',
            '======================================',
            '',
            'Parsing news...',
            '',
        ]);

        $response = $this->parseNews();
        $count = $this->saveNews($response);

        $output->writeln([
            '',
            'Parsing completed!',
            "{$count} new headlines saved.",
            '',
            '======================================',
        ]);

        return Command::SUCCESS;
    }

    /**
     * Parse News method
     */
    public function parseNews()
    {
        $reqData = [
            'query' => [
                'apiKey' => $_ENV["API_KEY"],
                'country' => 'us'
            ] 
        ];

        $response = $this->httpclient->request("GET", "https://newsapi.org/v2/top-headlines", $reqData);

        return json_decode($response->getContent());
    }

    /**
     * Save Parsed News method
     */
    public function saveNews($response)
    {
        //Get the parsed news
        $ndata = $this->parseNews()->articles;

        //Get the entity manager
        $dbmgr = $this->dbMagager->getManager();
        
        $count = 0;

        for ($i=0; $i < 20 ; $i++) { 
            
            $news = new News();
            $news->setTitle($ndata[$i]->title);
            $news->setDescription($ndata[$i]->description == null? "" : $ndata[$i]->description);
            $news->setPicture($ndata[$i]->urlToImage == null? "" : $ndata[$i]->urlToImage);
            $news->setCreatedAt(date("Y-m-d H:i:s", strtotime($ndata[$i]->publishedAt)));
            
            //Validate data
            $errors = $this->validator->validate($news);

            if(count($errors) > 0) {
                continue;
            }

            $dbmgr->persist($news);
            $dbmgr->flush();
            
           ++ $count;
        }

        return $count;
    }
}
