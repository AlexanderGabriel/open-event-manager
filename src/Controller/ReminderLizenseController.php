<?php

namespace App\Controller;

use App\Entity\License;
use App\Entity\Standort;
use App\Service\MailerService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use function Doctrine\ORM\QueryBuilder;

class ReminderLizenseController extends AbstractController
{
    /**
     * @Route("/reminder/lizense", name="reminder_lizense")
     */
    public function index(LoggerInterface $logger, Request $request, MailerService $mailerService, TranslatorInterface $translator,ParameterBagInterface $parameterBag): Response
    {
        if ($request->get('token') !== $parameterBag->get('cronToken')) {
            $message = ['error' => true, 'hinweis' => 'Token fehlerhaft', 'token' => $request->get('token'), 'ip' => $request->getClientIp()];
            $logger->error($message['hinweis'], $message);
            return new JsonResponse($message);
        }
        $counter = 0;
        $back = (new \DateTime())->modify('+5 days');
        $now = new \DateTime();
        $qb = $this->getDoctrine()->getRepository(License::class)->createQueryBuilder('license');
        $qb->andWhere($qb->expr()->gte('license.validUntil', ':now'))
            ->setParameter('now', $now)
            ->andWhere($qb->expr()->lte('license.validUntil', ':back'))
            ->setParameter('back', $back);
        $license = $qb->getQuery()->getResult();
        $em = $this->getDoctrine()->getManager();
        $error = false;
        $message = '';
        try {
            foreach ($license as $data) {
                $server = $this->getDoctrine()->getRepository(Standort::class)->findOneBy(array('licenseKey' => $data->getLicenseKey()));
                if($server){
                    $mailerService->sendEmail(
                        $server->getAdministrator()->getEmail(),
                        $translator->trans('Ihre Jitsi-Admin-Enterprise Lizenz läuft bald aus'),
                        $this->renderView('email/licenseReminder.html.twig', array('server' => $server, 'license' => $data)),
                        $server);
                    $counter++;
                }


            }
        }catch (\Exception $e){
            $error = true;
            $message = $e->getMessage();
        }

        return new JsonResponse(array('error' => $error,'message'=>$message, 'amount' => $counter));
    }
}
