<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Knp\Component\Pager\PaginatorInterface;
use App\Entity\News;

class NewsController extends AbstractController
{
    private array $data;

    private $dbMagager;
    private $paginator;
    
    /**
     * Constructor to initialize values
     */
    public function __construct(
        ManagerRegistry $dbMagager,
        PaginatorInterface $paginator
    ){
        $this->dbMagager = $dbMagager;
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

}
