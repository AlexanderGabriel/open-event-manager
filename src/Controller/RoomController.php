<?php

namespace App\Controller;

use App\Entity\Group;
use App\Entity\Rooms;
use App\Entity\Scheduling;
use App\Entity\Standort;
use App\Entity\User;
use App\Entity\UserEventCreated;
use App\Form\Type\NewMemberType;
use App\Form\Type\RoomType;
use App\Service\LoggerService;
use App\Service\ServerUserManagment;
use App\Service\UserEventCreateService;
use App\Service\UserService;
use App\Service\InviteService;

use App\Service\RoomService;
use phpDocumentor\Reflection\Types\This;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class RoomController extends AbstractController
{
    private $translator;
    private $logger;

    public function __construct(TranslatorInterface $translator, LoggerService $logger)
    {
        $this->translator = $translator;
        $this->logger = $logger;
    }

    /**
     * @Route("/room/new", name="room_new")
     */
    public function newRoom(Request $request, UserService $userService, TranslatorInterface $translator, ServerUserManagment $serverUserManagment, UserEventCreateService $userEventCreateService)
    {
        $roomOld = null;
        if ($request->get('id')) {
            $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(array('id' => $request->get('id')));
            if ($room->getModerator() !== $this->getUser()) {
                return $this->redirectToRoute('dashboard', ['snack' => $translator->trans('Keine Berechtigung')]);
            }

            $snack = $translator->trans('Event erfolgreich bearbeitet');
            $title = $translator->trans('Event bearbeiten');
            $sequence = $room->getSequence() + 1;
            $room->setSequence($sequence);
            if (!$room->getUidModerator()) {
                $room->setUidModerator(md5(uniqid('h2-invent', true)));
            }
            if (!$room->getUidParticipant()) {
                $room->setUidParticipant(md5(uniqid('h2-invent', true)));
            }
            $roomOld = clone $room;
        } else {
            $room = new Rooms();
            $room->addUser($this->getUser());

            $room->setDuration(60);
            $room->setUid(rand(01, 99) . time());
            $room->setModerator($this->getUser());
            $room->setSequence(0);
            $room->setUidReal(md5(uniqid('h2-invent', true)));
            $room->setUidModerator(md5(uniqid('h2-invent', true)));
            $room->setUidParticipant(md5(uniqid('h2-invent', true)));

            $snack = $translator->trans('Event erfolgreich erstellt');
            $title = $translator->trans('Neues Event planen');
        }
        $standort = $serverUserManagment->getStandortsFromUser($this->getUser());
        $freeFieldsOld = $room->getFreeFields()->toArray();

        $form = $this->createForm(RoomType::class, $room, ['standort' => $standort, 'action' => $this->generateUrl('room_new', ['id' => $room->getId()])]);

        $form->remove('scheduleMeeting');
        if (!$request->get('id')) {
            $form->remove('silentMode');
        }
        try {
            $form->handleRequest($request);
        } catch (\Exception $e) {
            $snack = $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.');
            return $this->redirectToRoute('dashboard', array('snack' => $snack, 'color' => 'danger'));
        }
        if ($form->isSubmitted() && $form->isValid()) {

            $room = $form->getData();
            if (!$room->getStart()) {
                $snack = $translator->trans('Fehler, Bitte kontrollieren Sie ihre Daten.');
                return $this->redirectToRoute('dashboard', array('snack' => $snack, 'color' => 'danger'));
            }
            $em = $this->getDoctrine()->getManager();
            foreach ($freeFieldsOld as $field){
                if(!in_array($field,$room->getFreeFields()->toArray())){
                    $em->remove($field);
                    $room->removeFreeField($field);
                }
            }
            $room->setEnddate((clone $room->getStart())->modify('+ ' . $room->getDuration() . ' minutes'));

            $em->persist($room);
            $em->flush();

            if (sizeof($room->getSchedulings()->toArray()) < 1) {
                $schedule = new Scheduling();
                $schedule->setUid(md5(uniqid()));
                $schedule->setRoom($room);
                $em->persist($schedule);
                $em->flush();
                $room->addScheduling($schedule);
                $em->persist($room);
                $em->flush();
            }

            if ($request->get('id')) {
                if (!$form['silentMode']->getData() ||
                    $roomOld->getStart() !== $room->getStart() ||
                    $roomOld->getDuration() !== $room->getDuration() ||
                    $roomOld->getEntryDateTime() !== $room->getEntryDateTime()
                ) {
                    foreach ($room->getUser() as $user) {
                        $userService->editRoom($user, $room);
                    }
                }

            } else {
                $userService->addUser($room->getModerator(), $room);
            }
            $modalUrl = base64_encode($this->generateUrl('room_add_user', array('room' => $room->getId())));
            if ($room->getScheduleMeeting()) {
                $modalUrl = base64_encode($this->generateUrl('schedule_admin', array('id' => $room->getId())));
            }
            return $this->redirectToRoute('dashboard', ['snack' => $snack, 'modalUrl' => $modalUrl]);


        }
        return $this->render('base/__newRoomModal.html.twig', array('form' => $form->createView(), 'title' => $title));
    }

    /**
     * @Route("/room/add-user", name="room_add_user")
     */
    public function roomAddUser(Request $request, InviteService $inviteService, UserService $userService)
    {
        $newMember = array();
        $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(['id' => $request->get('room')]);
        if ($room->getModerator() !== $this->getUser()) {
            return $this->redirectToRoute('dashboard', ['snack' => 'Keine Berechtigung']);
        }
        $form = $this->createForm(NewMemberType::class, $newMember, ['action' => $this->generateUrl('room_add_user', ['room' => $room->getId()])]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {


            $newMembers = $form->getData();
            $lines = explode("\n", $newMembers['member']);

            if (!empty($lines)) {
                $em = $this->getDoctrine()->getManager();
                $falseEmail = array();
                foreach ($lines as $line) {
                    $newMember = strtolower(trim($line));
                    if (filter_var($newMember, FILTER_VALIDATE_EMAIL)) {
                        $user = $inviteService->newUser($newMember);
                        $user->addRoom($room);
                        $user->addAddressbookInverse($room->getModerator());
                        $em->persist($user);
                        $snack = $this->translator->trans("Teilnehmer wurden eingeladen");
                        $userService->addUser($user, $room);
                    } else {
                        $falseEmail[] = $newMember;
                        $emails = implode(", ", $falseEmail);
                        $snack = $this->translator->trans("Einige Teilnehmer eingeladen. {emails} ist/sind nicht korrekt und können nicht eingeladen werden", array('{emails}' => $emails));
                    }
                }
                $em->flush();
                return $this->redirectToRoute('dashboard', ['snack' => $snack]);
            }
        }
        $title = $this->translator->trans('Teilnehmer verwalten');

        return $this->render('room/attendeeModal.twig', array('form' => $form->createView(), 'title' => $title, 'room' => $room));
    }

    /**
     * @Route("/room/join/{t}/{room}", name="room_join")
     * @ParamConverter("room", options={"mapping"={"room"="id"}})
     */
    public
    function joinRoom(RoomService $roomService, Rooms $room, $t)
    {

        if (in_array($this->getUser(), $room->getUser()->toarray())) {
            $url = $roomService->join($room, $this->getUser(), $t, $this->getUser()->getFirstName() . ' ' . $this->getUser()->getLastName());
            return $this->redirect($url);
        }

        return $this->redirectToRoute('dashboard', ['join_room' => $room->getId(), 'type' => $t]);
    }

    /**
     * @Route("/room/user/remove", name="room_user_remove")
     */
    public
    function roomUserRemove(Request $request, UserService $userService)
    {

        $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(['id' => $request->get('room')]);
        $user = $this->getDoctrine()->getRepository(User::class)->findOneBy(['id' => $request->get('user')]);
        $snack = 'Keine Berechtigung';
        $group = $this->getDoctrine()->getRepository(Group::class)->findOneBy(array('rooms' => $room, 'leader' => $user));

        if ($room->getModerator() === $this->getUser() || $user === $this->getUser()) {
            $room->removeUser($user);
            $room->addStorno($user);
            $em = $this->getDoctrine()->getManager();
            $em->persist($room);
            $this->logger->log('User was removed from Event', array('email' => $user->getEmail(), 'id' => $user->getId(), 'event' => $room->getId()));

            if ($user->isMemeberInGroup($room)) {
                $this->logger->log('User was removed from Group', array('email' => $user->getEmail(), 'id' => $user->getId(), 'event' => $room->getId()));

                $user->removeEventGroupsMemeber($user->isMemeberInGroup($room));
                $em->persist($user);
            }
            if ($group) {
                $this->logger->log('The User was a leader of a group so the whole group is deleted', array('email' => $user->getEmail(), 'id' => $user->getId(), 'event' => $room->getId(), 'group' => $group->getId()));

                foreach ($group->getMembers() as $data) {
                    $room->removeUser($data);
                    $room->addStorno($data);
                    $group->removeMember($data);
                    $userService->removeRoom($data, $room);
                    $em->persist($room);
                    $em->persist($group);
                    $this->logger->log('Remove User from Event', array('email' => $data->getEmail(), 'id' => $data->getId(), 'event' => $room->getId(), 'group' => $group->getId()));

                }
                $em->flush();
                $em->remove($group);
                $em->flush();
            }
            $em->flush();
            $snack = $this->translator->trans('Teilnehmer gelöscht');
            $userService->removeRoom($user, $room);
        }

        return $this->redirectToRoute('dashboard', ['snack' => $snack]);
    }

    /**
     * @Route("/room/remove", name="room_remove")
     */
    public
    function roomRemove(Request $request, UserService $userService)
    {

        $room = $this->getDoctrine()->getRepository(Rooms::class)->findOneBy(['id' => $request->get('room')]);
        $snack = 'Keine Berechtigung';
        if ($this->getUser() === $room->getModerator()) {
            $em = $this->getDoctrine()->getManager();
            foreach ($room->getUser() as $user) {
                if ($room->getEnddate() > new \DateTime()) {
                    $userService->removeRoom($user, $room);
                }
                $room->removeUser($user);
                $em->persist($room);
            }

            $room->setModerator(null);
            $em->persist($room);
            $em->flush();
            $snack = $this->translator->trans('Event gelöscht');
        }
        return $this->redirectToRoute('dashboard', ['snack' => $snack]);
    }

    /**
     * @Route("/room/clone", name="room_clone")
     */
    public
    function roomClone(Request $request, UserService $userService, TranslatorInterface $translator, ServerUserManagment $serverUserManagment)
    {

        $roomOld = $this->getDoctrine()->getRepository(Rooms::class)->find($request->get('room'));
        $room = clone $roomOld;
        $freeFieldsOld = $roomOld->getFreeFields()->toArray();
        foreach ($roomOld->getFreeFields() as $data){
            $freeTmp = clone $data;
            $room->addFreeField($freeTmp);
        }
        foreach ($freeFieldsOld as $data){
            $room->removeFreeField($data);
        }

        $room->setUid(rand(01, 99) . time());
        $room->setSequence(0);

        $snack = $translator->trans('Keine Berechtigung');
        $title = $translator->trans('Konferenz duplizieren');

        if ($this->getUser() === $room->getModerator()) {

            $standort = $serverUserManagment->getStandortsFromUser($this->getUser());

            $form = $this->createForm(RoomType::class, $room, ['standort' => $standort, 'action' => $this->generateUrl('room_clone', ['room' => $room->getId()])]);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $room = $form->getData();
                $room->setUidReal(md5(uniqid('h2-invent', true)));
                $room->setUidModerator(md5(uniqid()));
                $room->setUidParticipant(md5(uniqid()));
                $room->setSequence(0);
                $room->setUid(rand(0, 99) . time());
                $room->setEnddate((clone $room->getStart())->modify('+ ' . $room->getDuration() . ' minutes'));
                $em = $this->getDoctrine()->getManager();
                $em->persist($room);
                $em->flush();
                foreach ($roomOld->getUser() as $user) {
                    $userService->addUser($user, $room);
                }
                $snack = $translator->trans('Teilnehmer bearbeitet');
                return $this->redirectToRoute('dashboard', ['snack' => $snack, 'modalUrl' => base64_encode($this->generateUrl('room_add_user', array('room' => $room->getId())))]);
            }
            return $this->render('base/__newRoomModal.html.twig', array('form' => $form->createView(), 'title' => $title));
        }

        return $this->redirectToRoute('dashboard', ['snack' => $snack]);
    }

}
