<?php
/**
 * Created by PhpStorm.
 * User: Emanuel
 * Date: 03.10.2019
 * Time: 19:01
 */

namespace App\Service;

use App\Entity\Group;
use App\Entity\Rooms;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;


class UserService
{
    private $mailer;
    private $parameterBag;
    private $twig;
    private $notificationService;
    private $url;
    private $translator;
    private $em;

    public function __construct(EntityManagerInterface $entityManager, TranslatorInterface $translator, MailerService $mailerService, ParameterBagInterface $parameterBag, Environment $environment, NotificationService $notificationService, UrlGeneratorInterface $urlGenerator)
    {
        $this->mailer = $mailerService;
        $this->parameterBag = $parameterBag;
        $this->twig = $environment;
        $this->notificationService = $notificationService;
        $this->url = $urlGenerator;
        $this->translator = $translator;
        $this->em = $entityManager;
    }

    function generateUrl(Rooms $room, User $user)
    {

        $data = base64_encode('uid=' . $room->getUid() . '&email=' . $user->getEmail());
        $url = $this->parameterBag->get('laF_baseUrl') . $this->url->generate('join_index', ['data' => $data, 'slug' => $room->getStandort()->getSlug()]);
        return $url;
    }

    function addUser(User $user, Rooms $room)
    {
        if (!$user->getUid()) {
            $user->setUid(md5(uniqid()));
            $this->em->persist($user);
            $this->em->flush();
        }
        if (!$room->getScheduleMeeting()) {
            //we have a not sheduled meeting. So the participabts are getting invited directly

            $content = $this->twig->render('email/addUser.html.twig', ['user' => $user, 'room' => $room]);
            $subject = $this->translator->trans('Neue Einladung zu einem Event');
            $ics = $this->notificationService->createIcs($room, $user, 'REQUEST');
            $attachement[] = array('type' => 'text/calendar', 'filename' => $room->getName() . '.ics', 'body' => $ics);
            $this->notificationService->sendNotification($content, $subject, $user, $room->getStandort(), $attachement);
        } else {
            //we have a shedule Meting. the participants only got a link to shedule their appointments
            $content = $this->twig->render('email/scheduleMeeting.html.twig', ['user' => $user, 'room' => $room,]);
            $subject = $this->translator->trans('Neue Einladung zu einer Terminplanung');
            $this->notificationService->sendNotification($content, $subject, $user, $room->getStandort());
        }
        return true;
    }

    function addWaitinglist(User $user, Rooms $room)
    {
        if (!$user->getUid()) {
            $user->setUid(md5(uniqid()));
            $this->em->persist($user);
            $this->em->flush();
        }
        //we have a not sheduled meeting. So the participabts are getting invited directly
        $content = $this->twig->render('email/waitingList.html.twig', ['user' => $user, 'room' => $room]);
        $subject = $this->translator->trans('Hinzugefügt zur Warteliste');
        $this->notificationService->sendNotification($content, $subject, $user, $room->getStandort());
        $contentModerator = $this->twig->render('email/newUserOnWaitingList.html.twig',array('room'=>$room));
        $subjectModerator= $this->translator->trans('Neuer Teilnehmer auf der Warteliste für {name}',array('{name}'=>$room->getName()));
        $this->notificationService->sendNotification($contentModerator,$subjectModerator,$room->getModerator(),$room->getStandort());
        return true;
    }

    function editRoom(User $user, Rooms $room)
    {
        if (!$room->getScheduleMeeting()) {
            $url = $this->generateUrl($room, $user);
            $content = $this->twig->render('email/editRoom.html.twig', ['user' => $user, 'room' => $room, 'url' => $url]);
            $subject = $this->translator->trans('Event wurde bearbeitet');
            $ics = $this->notificationService->createIcs($room, $user, 'REQUEST');
            $attachement[] = array('type' => 'text/calendar', 'filename' => $room->getName() . '.ics', 'body' => $ics);
            $this->notificationService->sendNotification($content, $subject, $user, $room->getStandort(), $attachement);
        } else {
            //we have a shedule Meting. the participants only got a link to shedule their appointments
            $content = $this->twig->render('email/scheduleMeeting.html.twig', ['user' => $user, 'room' => $room,]);
            $subject = $this->translator->trans('Neue Einladung zu einer Terminplanung');
            $this->notificationService->sendNotification($content, $subject, $user, $room->getStandort());
        }
        return true;
    }

    function removeRoom(User $user, Rooms $room)
    {
        if (!$room->getScheduleMeeting()) {

            $content = $this->twig->render('email/removeRoom.html.twig', ['user' => $user, 'room' => $room,]);
            $subject = $this->translator->trans('Event wurde abgesagt');
            $ics = $this->notificationService->createIcs($room, $user,  'CANCEL');
            $attachement[] = array('type' => 'text/calendar', 'filename' => $room->getName() . '.ics', 'body' => $ics);
            $this->notificationService->sendNotification($content, $subject, $user, $room->getStandort(), $attachement);
        } else {
            $content = $this->twig->render('email/removeSchedule.html.twig', ['user' => $user, 'room' => $room,]);
            $subject = $this->translator->trans('Terminplanung abgesagt');
            $this->notificationService->sendNotification($content, $subject, $user, $room->getStandort());
        }
        return true;
    }

    function notifyUser(User $user, Rooms $room)
    {
        $url = $this->generateUrl($room, $user);
        $content = $this->twig->render('email/rememberUser.html.twig', ['user' => $user, 'room' => $room, 'url' => $url]);
        $subject = $this->translator->trans('Event {room} startet gleich', array('{room}' => $room->getName()));
        $this->notificationService->sendCron($content, $subject, $user, $room->getStandort());
        return true;
    }
    function removeGroupFromRoom(Group $group){
        $room = $group->getRooms();
        foreach ($group->getMembers() as $data){
            $room->removeUser($data);
            $this->em->persist($data);
            $content = $this->twig->render('email/removeRoom.html.twig', ['user' => $data, 'room' => $room,]);
            $subject = $this->translator->trans('Event wurde abgesagt');
            $ics = $this->notificationService->createIcs($room, $data,  'CANCEL');
            $attachement[] = array('type' => 'text/calendar', 'filename' => $room->getName() . '.ics', 'body' => $ics);
            $this->notificationService->sendNotification($content, $subject, $data, $room->getStandort(), $attachement);
        }
        $this->em->flush();
    }

}
