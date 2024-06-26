<?php
// src/Entity/User.php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Entity\UserBase as BaseUser;
use Symfony\Component\Validator\Constraints as Assert;
use App\Repository\UserRepository;

/**
 * @ORM\Table(name="fos_user")
 * @ORM\Entity(repositoryClass=UserRepository::class)
 */
class User extends BaseUser
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @Assert\NotBlank(message="fos_user.password.blank", groups={"Registration", "ResetPassword", "ChangePassword"})
     * @Assert\Length(min=8,
     *     minMessage="fos_user.password.short",
     *     groups={"Registration", "Profile", "ResetPassword", "ChangePassword"})
     */
    protected $plainPassword;

    /**
     * @ORM\Column(type="text",nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $keycloakId;

    /**
     * @ORM\Column(type="datetime",nullable=true)
     */
    private $createdAt;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $username;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $lastLogin;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $firstName;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $lastName;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $registerId;

    /**
     * @ORM\ManyToMany(targetEntity=Rooms::class, mappedBy="user")
     */
    private $rooms;

    /**
     * @ORM\ManyToMany(targetEntity=Standort::class, mappedBy="user")
     */
    private $standort;

    /**
     * @ORM\OneToMany(targetEntity=Rooms::class, mappedBy="moderator")
     */
    private $roomModerator;

    /**
     * @ORM\OneToMany(targetEntity=Standort::class, mappedBy="administrator")
     */
    private $standortAdmins;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, inversedBy="addressbookInverse")
     */
    private $addressbook;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, mappedBy="addressbook")
     */
    private $addressbookInverse;

    /**
     * @ORM\OneToMany(targetEntity=RoomsUser::class, mappedBy="user")
     */
    private $roomsAttributes;

    /**
     * @ORM\OneToMany(targetEntity=Subscriber::class, mappedBy="user")
     */
    private $subscribers;

    /**
     * @ORM\Column(type="array", nullable=true,name="keycloakGroup")
     */
    private $groups = [];

    /**
     * @ORM\OneToMany(targetEntity=SchedulingTimeUser::class, mappedBy="user")
     */
    private $schedulingTimeUsers;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $uid;

    /**
     * @ORM\OneToMany(targetEntity=Waitinglist::class, mappedBy="user")
     */
    private $waitinglists;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $phone;

    /**
     * @ORM\ManyToMany(targetEntity=Rooms::class, mappedBy="storno")
     */
    private $roomsStorno;

    /**
     * @ORM\OneToMany(targetEntity=Group::class, mappedBy="leader")
     */
    private $eventGroups;

    /**
     * @ORM\ManyToMany(targetEntity=Group::class, mappedBy="members")
     */
    private $eventGroupsMemebers;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $address;

    /**
     * @ORM\OneToMany(targetEntity=FreeFieldsUserAnswer::class, mappedBy="user")
     */
    private $freeFieldsUserAnswers;

    /**
     * @ORM\OneToMany(targetEntity=UserEventCreated::class, mappedBy="user", orphanRemoval=true)
     */
    private $userEventCreateds;



    public function __construct()
    {
        $this->rooms = new ArrayCollection();
        $this->standort = new ArrayCollection();
        $this->roomModerator = new ArrayCollection();
        $this->standortAdmins = new ArrayCollection();
        $this->addressbook = new ArrayCollection();
        $this->addressbookInverse = new ArrayCollection();
        $this->roomsAttributes = new ArrayCollection();
        $this->subscribers = new ArrayCollection();
        $this->schedulingTimeUsers = new ArrayCollection();
        $this->waitinglists = new ArrayCollection();
        $this->roomsStorno = new ArrayCollection();
        $this->eventGroups = new ArrayCollection();
        $this->eventGroupsMemebers = new ArrayCollection();
        $this->freeFieldsUserAnswers = new ArrayCollection();
        $this->userEventCreateds = new ArrayCollection();

    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        if ($email){
            $this->email = strtolower($email);
        }


        return $this;
    }

    public function getKeycloakId(): ?string
    {
        return $this->keycloakId;
    }

    public function setKeycloakId(?string $keycloakId): self
    {
        $this->keycloakId = $keycloakId;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function setUsername(?string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getLastLogin(): ?\DateTimeInterface
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTimeInterface $lastLogin): self
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getRegisterId(): ?string
    {
        return $this->registerId;
    }

    public function setRegisterId(?string $registerId): self
    {
        $this->registerId = $registerId;

        return $this;
    }

    /**
     * @return Collection|Rooms[]
     */
    public function getRooms(): Collection
    {
        return $this->rooms;
    }

    public function addRoom(Rooms $room): self
    {
        if (!$this->rooms->contains($room)) {
            $this->rooms[] = $room;
            $room->addUser($this);
        }

        return $this;
    }

    public function removeRoom(Rooms $room): self
    {
        if ($this->rooms->removeElement($room)) {
            $room->removeUser($this);
        }

        return $this;
    }

    /**
     * @return Collection|Standort[]
     */
    public function getStandort(): Collection
    {
        return $this->standort;
    }

    public function addStandort(Standort $standort): self
    {
        if (!$this->standort->contains($standort)) {
            $this->standort[] = $standort;
            $standort->addUser($this);
        }

        return $this;
    }

    public function removeStandort(Standort $standort): self
    {
        if ($this->standort->removeElement($standort)) {
            $standort->removeUser($this);
        }

        return $this;
    }

    /**
     * @return Collection|Rooms[]
     */
    public function getRoomModerator(): Collection
    {
        return $this->roomModerator;
    }

    public function addRoomModerator(Rooms $roomModerator): self
    {
        if (!$this->roomModerator->contains($roomModerator)) {
            $this->roomModerator[] = $roomModerator;
            $roomModerator->setModerator($this);
        }

        return $this;
    }

    public function removeRoomModerator(Rooms $roomModerator): self
    {
        if ($this->roomModerator->removeElement($roomModerator)) {
            // set the owning side to null (unless already changed)
            if ($roomModerator->getModerator() === $this) {
                $roomModerator->setModerator(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Standort[]
     */
    public function getStandortAdmins(): Collection
    {
        return $this->standortAdmins;
    }

    public function addStandortAdmin(Standort $serverAdmin): self
    {
        if (!$this->standortAdmins->contains($serverAdmin)) {
            $this->standortAdmins[] = $serverAdmin;
            $serverAdmin->setAdministrator($this);
        }

        return $this;
    }

    public function removeStandortAdmin(Standort $standortAdmin): self
    {
        if ($this->standortAdmins->removeElement($standortAdmin)) {
            // set the owning side to null (unless already changed)
            if ($standortAdmin->getAdministrator() === $this) {
                $standortAdmin->setAdministrator(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getAddressbook(): Collection
    {
        return $this->addressbook;
    }

    public function addAddressbook(self $addressbook): self
    {
        if (!$this->addressbook->contains($addressbook)) {
            $this->addressbook[] = $addressbook;
        }

        return $this;
    }

    public function removeAddressbook(self $addressbook): self
    {
        $this->addressbook->removeElement($addressbook);

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getAddressbookInverse(): Collection
    {
        return $this->addressbookInverse;
    }

    public function addAddressbookInverse(self $addressbookInverse): self
    {
        if (!$this->addressbookInverse->contains($addressbookInverse)) {
            $this->addressbookInverse[] = $addressbookInverse;
            $addressbookInverse->addAddressbook($this);
        }

        return $this;
    }

    public function removeAddressbookInverse(self $addressbookInverse): self
    {
        if ($this->addressbookInverse->removeElement($addressbookInverse)) {
            $addressbookInverse->removeAddressbook($this);
        }

        return $this;
    }

    /**
     * @return Collection|RoomsUser[]
     */
    public function getRoomsAttributes(): Collection
    {
        return $this->roomsAttributes;
    }

    public function addRoomsAttributes(RoomsUser $roomsNew): self
    {
        if (!$this->roomsAttributes->contains($roomsNew)) {
            $this->roomsAttributes[] = $roomsNew;
            $roomsNew->setUser($this);
        }

        return $this;
    }

    public function removeRoomsAttributes(RoomsUser $roomsNew): self
    {
        if ($this->roomsAttributes->removeElement($roomsNew)) {
            // set the owning side to null (unless already changed)
            if ($roomsNew->getUser() === $this) {
                $roomsNew->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Subscriber[]
     */
    public function getSubscribers(): Collection
    {
        return $this->subscribers;
    }

    public function addSubscriber(Subscriber $subscriber): self
    {
        if (!$this->subscribers->contains($subscriber)) {
            $this->subscribers[] = $subscriber;
            $subscriber->setUser($this);
        }

        return $this;
    }

    public function removeSubscriber(Subscriber $subscriber): self
    {
        if ($this->subscribers->removeElement($subscriber)) {
            // set the owning side to null (unless already changed)
            if ($subscriber->getUser() === $this) {
                $subscriber->setUser(null);
            }
        }

        return $this;
    }

    public function getGroups(): ?array
    {
        return $this->groups;
    }

    public function setGroups(?array $groups): self
    {
        $this->groups = $groups;

        return $this;
    }

    /**
     * @return Collection|SchedulingTimeUser[]
     */
    public function getSchedulingTimeUsers(): Collection
    {
        return $this->schedulingTimeUsers;
    }

    public function addSchedulingTimeUser(SchedulingTimeUser $schedulingTimeUser): self
    {
        if (!$this->schedulingTimeUsers->contains($schedulingTimeUser)) {
            $this->schedulingTimeUsers[] = $schedulingTimeUser;
            $schedulingTimeUser->setUser($this);
        }

        return $this;
    }

    public function removeSchedulingTimeUser(SchedulingTimeUser $schedulingTimeUser): self
    {
        if ($this->schedulingTimeUsers->removeElement($schedulingTimeUser)) {
            // set the owning side to null (unless already changed)
            if ($schedulingTimeUser->getUser() === $this) {
                $schedulingTimeUser->setUser(null);
            }
        }

        return $this;
    }

    public function getUid(): ?string
    {
        return $this->uid;
    }

    public function setUid(?string $uid): self
    {
        $this->uid = $uid;

        return $this;
    }

    /**
     * @return Collection|Waitinglist[]
     */
    public function getWaitinglists(): Collection
    {
        return $this->waitinglists;
    }

    public function addWaitinglist(Waitinglist $waitinglist): self
    {
        if (!$this->waitinglists->contains($waitinglist)) {
            $this->waitinglists[] = $waitinglist;
            $waitinglist->setUser($this);
        }

        return $this;
    }

    public function removeWaitinglist(Waitinglist $waitinglist): self
    {
        if ($this->waitinglists->removeElement($waitinglist)) {
            // set the owning side to null (unless already changed)
            if ($waitinglist->getUser() === $this) {
                $waitinglist->setUser(null);
            }
        }

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return Collection|Rooms[]
     */
    public function getRoomsStorno(): Collection
    {
        return $this->roomsStorno;
    }

    public function addRoomsStorno(Rooms $roomsStorno): self
    {
        if (!$this->roomsStorno->contains($roomsStorno)) {
            $this->roomsStorno[] = $roomsStorno;
            $roomsStorno->addStorno($this);
        }

        return $this;
    }

    public function removeRoomsStorno(Rooms $roomsStorno): self
    {
        if ($this->roomsStorno->removeElement($roomsStorno)) {
            $roomsStorno->removeStorno($this);
        }

        return $this;
    }

    /**
     * @return Collection|Group[]
     */
    public function getEventGroups(): Collection
    {
        return $this->eventGroups;
    }

    public function addEventGroup(Group $eventGroup): self
    {
        if (!$this->eventGroups->contains($eventGroup)) {
            $this->eventGroups[] = $eventGroup;
            $eventGroup->setLeader($this);
        }

        return $this;
    }

    public function removeEventGroup(Group $eventGroup): self
    {
        if ($this->eventGroups->removeElement($eventGroup)) {
            // set the owning side to null (unless already changed)
            if ($eventGroup->getLeader() === $this) {
                $eventGroup->setLeader(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Group[]
     */
    public function getEventGroupsMemebers(): Collection
    {
        return $this->eventGroupsMemebers;
    }

    public function addEventGroupsMemeber(Group $eventGroupsMemeber): self
    {
        if (!$this->eventGroupsMemebers->contains($eventGroupsMemeber)) {
            $this->eventGroupsMemebers[] = $eventGroupsMemeber;
            $eventGroupsMemeber->addMember($this);
        }

        return $this;
    }

    public function removeEventGroupsMemeber(Group $eventGroupsMemeber): self
    {
        if ($this->eventGroupsMemebers->removeElement($eventGroupsMemeber)) {
            $eventGroupsMemeber->removeMember($this);
        }

        return $this;
    }

    public function isMemeberInGroup(Rooms $rooms): ?Group{
        foreach ($this->eventGroupsMemebers as $data){
            if($data->getRooms() == $rooms){
                return $data;
            }
        }
        return null;
    }
    public function isLeaderofGroup(Rooms $rooms): ?Group{
        foreach ($this->eventGroups as $data){
            if($data->getRooms() == $rooms){
                return $data;
            }
        }
        return null;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return Collection|FreeFieldsUserAnswer[]
     */
    public function getFreeFieldsUserAnswers(): Collection
    {
        return $this->freeFieldsUserAnswers;
    }

    public function addFreeFieldsUserAnswer(FreeFieldsUserAnswer $freeFieldsUserAnswer): self
    {
        if (!$this->freeFieldsUserAnswers->contains($freeFieldsUserAnswer)) {
            $this->freeFieldsUserAnswers[] = $freeFieldsUserAnswer;
            $freeFieldsUserAnswer->setUser($this);
        }

        return $this;
    }

    public function removeFreeFieldsUserAnswer(FreeFieldsUserAnswer $freeFieldsUserAnswer): self
    {
        if ($this->freeFieldsUserAnswers->removeElement($freeFieldsUserAnswer)) {
            // set the owning side to null (unless already changed)
            if ($freeFieldsUserAnswer->getUser() === $this) {
                $freeFieldsUserAnswer->setUser(null);
            }
        }

        return $this;
    }
    public function getFreeFieldsFromRoom(Rooms $rooms){
        $res = array();
        foreach ($this->freeFieldsUserAnswers as $data){
            if ($data->getFreeField()->getRoom() == $rooms){
                $res[] = $data;
            }
        }
        return $res;
    }

    /**
     * @return Collection|UserEventCreated[]
     */
    public function getUserEventCreateds(): Collection
    {
        return $this->userEventCreateds;
    }

    public function addUserEventCreated(UserEventCreated $userEventCreated): self
    {
        if (!$this->userEventCreateds->contains($userEventCreated)) {
            $this->userEventCreateds[] = $userEventCreated;
            $userEventCreated->setUser($this);
        }

        return $this;
    }

    public function removeUserEventCreated(UserEventCreated $userEventCreated): self
    {
        if ($this->userEventCreateds->removeElement($userEventCreated)) {
            // set the owning side to null (unless already changed)
            if ($userEventCreated->getUser() === $this) {
                $userEventCreated->setUser(null);
            }
        }

        return $this;
    }

}
