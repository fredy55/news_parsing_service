<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Knp\Component\Pager\PaginatorInterface;
use App\Entity\News;

class NewsController extends AbstractController
{
    private array $data;
    private $httpclient;

    private $dbMagager;
    private $validator;
    private $paginator;
    
    /**
     * Constructor to initialize values
     */
    public function __construct(
        HttpClientInterface $httpClient,
        ManagerRegistry $dbMagager,
        ValidatorInterface $validator,
        PaginatorInterface $paginator
    ){
        $this->httpclient = $httpClient;
        $this->dbMagager = $dbMagager;
        $this->validator = $validator;
        $this->paginator = $paginator;
    }
    
    #[Route('/', name: 'app_news', methods: "GET")]
    public function index(Request $request)
    {
        //$response = $this->parseNews();
        //$this->saveNews($response);

        //Get the entity manager
        $dbmgr = $this->dbMagager->getManager();

        //Get the entity repository
        $newsRepo = $dbmgr->getRepository(News::class);

        $news = $newsRepo->createQueryBuilder('n')
                        ->orderBy('n.id', 'DESC')
                        ->getQuery();
        
        //News per page
        $perpage = 10;

        //Paginated news list
        $data['news'] = $this->paginator->paginate($news, $request->query->getInt('page', 1), $perpage);

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
        }


    }
}
