<?php

namespace App\Entity\POS;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\POS\EmployeeRepository")
 */
class Employee
{
    const DEFAULT_SORT_FIELD = 'id';
    const ALLOWED_SORT_FIELDS = [
        'id'            => 'id',
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var Workspace
     *
     * @Assert\NotBlank
     * @ORM\ManyToOne(targetEntity="App\Entity\POS\Workspace")
     * @ORM\JoinColumn(name="workspace_id", referencedColumnName="id")
     */
    private $workspace;

    /**
     * @var string
     *
     * @Assert\NotBlank(message = "First name should not be blank.")
     * @Assert\Regex(message = "First name is not a valid.", pattern="/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ -]+$/i")
     * @ORM\Column(type="string", length=128)
     */
    private $firstName;

    /**
     * @var string
     *
     * @Assert\NotBlank(message = "Last name should not be blank.")
     * @Assert\Regex(message = "Last name is not a valid.", pattern="/^[a-zA-ZąćęłńóśźżĄĆĘŁŃÓŚŹŻ -]+$/i")
     * @ORM\Column(type="string", length=128)
     */
    private $lastName;

    /**
     * @var int
     * @Assert\Type(type="integer", message="The value {{ value }} is not a valid type.")
     * @Assert\GreaterThan(value = 100000)
     * @ORM\Column(type="integer")
     */
    private $pin;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $enabled = true;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $printerEnabled = true;

    /**
     * @var string|null
     *
     * @Assert\NotBlank(message = "Printer MAC Address should not be blank.")
     * @ORM\Column(type="string", length=128, nullable=true)
     */
    private $printerMac;

    /**
     * @var string
     *
     * @Assert\NotBlank(message = "Printer Username should not be blank.")
     * @ORM\Column(type="string", length=128)
     */
    private $printerUsername;

    /**
     * @var int
     * @Assert\Type(type="integer", message="The value {{ value }} is not a valid type.")
     * @Assert\GreaterThan(value = 100000)
     * @ORM\Column(type="integer")
     */
    private $printerPin;

    /**
     * Employee constructor.
     * @param Workspace $workspace
     * @param string $firstName
     * @param string $lastName
     * @param int $pin
     */
    public function __construct(Workspace $workspace, string $firstName, string $lastName, int $pin)
    {
        $this->workspace = $workspace;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->pin = $pin;

        $this->setEnabled(true);
        $this->setPrinterEnabled(false);
        $this->setPrinterMac('0.0.0.0');
        $this->setPrinterUsername(md5($this->firstName.$this->lastName.$this->pin));
        $this->setPrinterPin(rand(100000, 1000000));
    }

    /**
     * Serialize and return public data of the object
     *
     * @param bool $extended
     * @return array
     */
    public function serialize(bool $extended = false) : array
    {
        $serialized = [
            'id'            => $this->id,
            'firstName'     => $this->firstName,
            'lastName'      => $this->lastName,
            'workspace'     => $this->workspace->serialize(),
            'isEnabled'     => $this->isEnabled(),
        ];

        if($extended){
        }

        return $serialized;
    }

    /**
     * Serialize and return public basic data of the object
     *
     * @return array
     */
    public function serializeBasic() : array
    {
        return [
            'id'            => $this->id,
            'firstName'     => $this->firstName,
            'lastName'      => $this->lastName,
            'isEnabled'     => $this->isEnabled(),
        ];
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Workspace
     */
    public function getWorkspace(): Workspace
    {
        return $this->workspace;
    }

    /**
     * @param Workspace $workspace
     */
    public function setWorkspace(Workspace $workspace): void
    {
        $this->workspace = $workspace;
    }

    /**
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param mixed $firstName
     */
    public function setFirstName($firstName): void
    {
        $this->firstName = $firstName;
    }

    /**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param mixed $lastName
     */
    public function setLastName($lastName): void
    {
        $this->lastName = $lastName;
    }

    /**
     * @return int
     */
    public function getPin(): int
    {
        return $this->pin;
    }

    /**
     * @param int $pin
     */
    public function setPin(int $pin): void
    {
        $this->pin = $pin;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    /**
     * @return bool
     */
    public function isPrinterEnabled(): bool
    {
        return $this->printerEnabled;
    }

    /**
     * @param bool $printerEnabled
     */
    public function setPrinterEnabled(bool $printerEnabled): void
    {
        $this->printerEnabled = $printerEnabled;
    }

    /**
     * @return string|null
     */
    public function getPrinterMac(): ?string
    {
        return $this->printerMac;
    }

    /**
     * @param string|null $printerMac
     */
    public function setPrinterMac(?string $printerMac): void
    {
        $this->printerMac = $printerMac;
    }

    /**
     * @return string
     */
    public function getPrinterUsername(): string
    {
        return $this->printerUsername;
    }

    /**
     * @param string $printerUsername
     */
    public function setPrinterUsername(string $printerUsername): void
    {
        $this->printerUsername = $printerUsername;
    }

    /**
     * @return int
     */
    public function getPrinterPin(): int
    {
        return $this->printerPin;
    }

    /**
     * @param int $printerPin
     */
    public function setPrinterPin(int $printerPin): void
    {
        $this->printerPin = $printerPin;
    }
}
