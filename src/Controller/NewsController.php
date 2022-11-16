<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Entity\News;

class NewsController extends AbstractController
{
    private array $data;
    private $httpclient;

    private $dbMagager;
    private $validator;
    
    /**
     * Constructor to initialize values
     */
    public function __construct(
        HttpClientInterface $httpClient,
        ManagerRegistry $dbMagager,
        ValidatorInterface $validator
    ){
        $this->httpclient = $httpClient;
        $this->dbMagager = $dbMagager;
        $this->validator = $validator;
    }
    
    #[Route('/', name: 'app_news', methods: "GET")]
    public function index(): Response
    {
        
        //$response = $this->parseNews();
        //$this->saveNews($response); //Save news
        //dd($response->articles);

        //Get the entity manager
        $dbmgr = $this->dbMagager->getManager();

        //Get the entity repository
        $newsRepo = $dbmgr->getRepository(News::class);
        $news = $newsRepo->createQueryBuilder('n')
                        ->orderBy('n.id', 'DESC')
                        ->getQuery();
        
        //News per page
        $perpage = 10;

        //News page
        $page = 2;
                        
        $paginator = new Paginator($news);
        //dd(count($paginator));
        
        //Total news
        $data['ncount'] = count($paginator);

        //Paginated news list
        $data['news'] = $paginator->getQuery()
                        ->setFirstResult($perpage * ($page - 1))
                        ->setMaxResults(10)
                        ->getResult();
        //dd($data['news']);
        return $this->render('news/index.html.twig', $data);
    }

    /**
     * Parse News method
     */
    public function parseNews()
    {
        $reqData = [
            'query' => [
                'apiKey' => $_ENV["API_KEY"],
                'country' => 'de'
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

        for ($i=0; $i < 20 ; $i++) { 
            $news = new News();
            $news->setTitle($ndata[$i]->title);
            $news->setDescription($ndata[$i]->description);
            $news->setPicture($ndata[$i]->urlToImage);
            $news->setCreatedAt(date("Y-m-d H:i:s", strtotime($ndata[$i]->publishedAt)));
            
            //Validate data
            $errors = $this->validator->validate($news);

            if(count($errors) > 0) {
                continue;
            }

            $dbmgr->persist($news);
            $dbmgr->flush();
        }


    }
}
