 namespace App\Controller;

 use Symfony\Component\HttpFoundation\JsonResponse;
 use Symfony\Component\Routing\Annotation\Route;

 class TestController
 {
 #[Route('/api/ping', name: 'api_ping', methods: ['GET'])]
 public function ping(): JsonResponse
 {
 return new JsonResponse(['pong' => true]);
 }
 }