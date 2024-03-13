<?php

namespace App\Controller;

use App\Entity\Tarea;
use App\Entity\User;
use App\Repository\TareaRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;


class TareasController extends AbstractController
{

    private $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }



    #[Route('/tareas', name: 'app_tareas')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        return $this->render('tareas/index.html', [
        ]);
    }

    #[Route('/api/allTareasUser', name: 'allTareas')]
    #[IsGranted('ROLE_USER')]
    public function getAll(UserRepository $userRepo, EntityManagerInterface $entityManager): JsonResponse
    {

        //Todo lo que esta comentao es otra manera de hacerlo
        $email = $this->getUser()->getUserIdentifier();

        $user = $userRepo->findUserByEmail($email);

        $tareas = $user[0]->getTareas();
        //$entityManager->getRepository(Tarea::class)->findBy(['user' => $user]);

        /* $defaultContext = [
            AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function (object $object, string $format, array $context): string {
                return $object->getId();
            },
        ];
 */

        /* $result = $serializer->normalize($tareas, "null", [AbstractObjectNormalizer::ENABLE_MAX_DEPTH => true]); */

        

        return $this->toJSON($tareas);
    }


    #[Route('/api/borrar/{id}', name: 'borrar')]
    #[IsGranted('ROLE_USER')]
    public function borrar($id, TareaRepository $tareaRepository, EntityManagerInterface $entityManager, UserRepository $userRepository): JsonResponse
    {
        
        $tareaBorrar = $tareaRepository->find($id);

        //Controlar si no existe o es de otro usuario
        if($tareaBorrar==null || $this->getUser()->getUserIdentifier()!=$tareaBorrar->getUser()->getEmail()){
            return $this->json(['respuesta'=>'Vas a pillar Samuel']);
        }

        $tareaBorrar->setUser(null);
        
        if($tareaBorrar==null){
            return $this->json(['respuesta'=>'no']);
        }

        $entityManager->remove($tareaBorrar);
        $entityManager->flush();
        
        $newTarea = $tareaRepository->find($id);
        if($newTarea==null){
            return $this->json(['respuesta'=>'ok']);
        }
        
        return $this->json(['respuesta'=>'no']);
    }
    

    #[Route('/api/insertar', name: 'insertar')]
    #[IsGranted('ROLE_USER')]
    public function insertar(UserRepository $userRepo, TareaRepository $tareaRepository, EntityManagerInterface $entityManager, Request $request): JsonResponse
    {
        $email = $this->getUser()->getUserIdentifier();
        $user = $userRepo->findUserByEmail($email)[0];

        $tarea = new Tarea();

        $contenido = json_decode($request->getContent(), true);
        $texto = $contenido['texto'];
        
        $tarea->setTexto($texto);
        $tarea->setUser($user);
        $tarea->setFecha(new \DateTime());

        $entityManager->persist($tarea);
        $entityManager->flush();
        $tarea->setUser(null); //Para que no de error de referencia circular

        
        

        return $this->json([
            'id'=>$tarea->getId(),
            'texto'=>$tarea->getTexto(),
            'fecha'=>$tarea->getFecha(),
        ]);
    }



    function toJSON($tarea): JsonResponse {
        $otroContext = [
            AbstractNormalizer::IGNORED_ATTRIBUTES=>['user'],
        ];

        $encoder = new JsonEncoder();

        $normalizer = new ObjectNormalizer(null, null, null, null, null, null, $otroContext);

        $serializer = new Serializer([$normalizer], [$encoder]);

        /* $result = $serializer->normalize($tareas, "null", [AbstractObjectNormalizer::ENABLE_MAX_DEPTH => true]); */

        $resultado = $serializer->serialize($tarea, 'json');

        return new JsonResponse($resultado,200,[],true);
    }
   
}
