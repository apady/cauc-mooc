<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\Mapping\JoinTable;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass="App\Repository\CoursesRepository")
 * @ORM\Table(name="courses")
 * @ORM\HasLifecycleCallbacks()
 */
class Courses extends AbstractEntity
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\User", mappedBy="coursesSelected")
     *
     */
    private $students;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="coursesToTeach")
     * @ORM\JoinColumn(nullable=false)
     */
    private $teacher;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\File", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     * @Assert\Type(type="App\Entity\File")
     * @Assert\Valid()
     */
    private $coverImg;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $courseNumber;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="text")
     */
    private $info;

    /**
     * @ORM\Column(type="integer")
     */
    private $courseHour;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Task", mappedBy="course")
     */
    private $tasks;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Tags", mappedBy="course")
     */
    private $tags;

    /**
     *  *课程资源
     * @ManyToMany(targetEntity="File")
     * @JoinTable(name="course_resource",
     *      joinColumns={@JoinColumn(name="course_id", referencedColumnName="id")},
     *      inverseJoinColumns={@JoinColumn(name="file_id", referencedColumnName="id", unique=true)}
     *    )
     */
    private $resourceFiles;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updatedAt;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Category", inversedBy="courses")
     */
    private $category;

    /**
     * @ORM\Column(type="integer")
     */
    private $capacity;

    /**
     * @ORM\Column(type="boolean")
     */
    private $selectable;

    /**
     * @ORM\Column(type="datetime",nullable=true)
     */
    private $selection_begin;

    /**
     * @ORM\Column(type="datetime",nullable=true)
     */
    private $selection_end;

    public function __construct()
    {
        $this->students = new ArrayCollection();
        $this->tasks = new ArrayCollection();
        $this->tags = new ArrayCollection();
        $this->resourceFiles = new ArrayCollection();
        $this->category = new ArrayCollection();
        $this->selectable=true;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection|User[]
     */
    public function getStudents(): Collection
    {
        return $this->students;
    }

    public function addStudent(User $student): self
    {
        if (!$this->students->contains($student)) {
            $this->students[] = $student;
            $student->addCoursesSelected($this);
        }

        return $this;
    }

    public function removeStudent(User $student): self
    {
        if ($this->students->contains($student)) {
            $this->students->removeElement($student);
            $student->removeCoursesSelected($this);
        }

        return $this;
    }

    public function removeAllStudent(): self
    {
        $this->students->clear();
        return $this;
    }

    public function getTeacher(): ?User
    {
        return $this->teacher;
    }

    public function setTeacher(?User $teacher): self
    {
        $this->teacher = $teacher;

        return $this;
    }

    public function getCoverImg(): ?File
    {
        return $this->coverImg;
    }

    public function setCoverImg(File $coverImg): self
    {
        $this->coverImg = $coverImg;

        return $this;
    }

    public function getCourseNumber(): ?string
    {
        return $this->courseNumber;
    }

    public function setCourseNumber(string $courseNumber): self
    {
        $this->courseNumber = $courseNumber;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getInfo(): ?string
    {
        return $this->info;
    }

    public function setInfo(string $info): self
    {
        $this->info = $info;

        return $this;
    }

    public function getCourseHour(): ?int
    {
        return $this->courseHour;
    }

    public function setCourseHour(int $courseHour): self
    {
        $this->courseHour = $courseHour;

        return $this;
    }

    /**
     * @return Collection|Task[]
     */
    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function addTask(Task $task): self
    {
        if (!$this->tasks->contains($task)) {
            $this->tasks[] = $task;
            $task->setCourse($this);
        }

        return $this;
    }

    public function removeTask(Task $task): self
    {
        if ($this->tasks->contains($task)) {
            $this->tasks->removeElement($task);
            // set the owning side to null (unless already changed)
            if ($task->getCourse() === $this) {
                $task->setCourse(null);
            }
        }

        return $this;
    }

    public function removeAllTasks(): self
    {
        $this->tasks->clear();
        return $this;
    }

    /**
     * @return Collection|Tags[]
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tags $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags[] = $tag;
            $tag->setCourse($this);
        }

        return $this;
    }

    public function removeTag(Tags $tag): self
    {
        if ($this->tags->contains($tag)) {
            $this->tags->removeElement($tag);
            // set the owning side to null (unless already changed)
            if ($tag->getCourse() === $this) {
                $tag->setCourse(null);
            }
        }

        return $this;
    }

    public function removeAllTags(): self
    {
        $this->tags->clear();
        return $this;
    }

    /**
     * @return Collection|File[]
     */
    public function getResourceFiles(): Collection
    {
        return $this->resourceFiles;
    }

    public function addResourceFile(File $resourceFile): self
    {
        if (!$this->resourceFiles->contains($resourceFile)) {
            $this->resourceFiles[] = $resourceFile;
        }

        return $this;
    }

    public function removeResourceFile(File $resourceFile): self
    {
        if ($this->resourceFiles->contains($resourceFile)) {
            $this->resourceFiles->removeElement($resourceFile);
        }

        return $this;
    }

    public function removeAllResourceFile(): self
    {
        $this->resourceFiles->clear();
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

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection|Category[]
     */
    public function getCategory(): Collection
    {
        return $this->category;
    }

    public function addCategory(Category $category): self
    {
        if (!$this->category->contains($category)) {
            $this->category[] = $category;
        }

        return $this;
    }

    public function removeCategory(Category $category): self
    {
        if ($this->category->contains($category)) {
            $this->category->removeElement($category);
        }

        return $this;
    }

    public function getCapacity(): ?int
    {
        return $this->capacity;
    }

    public function setCapacity(int $capacity): self
    {
        $this->capacity = $capacity;

        return $this;
    }

    public function getSelectable(): ?bool
    {
        return $this->selectable;
    }

    public function setSelectable(bool $selectable): self
    {
        $this->selectable = $selectable;

        return $this;
    }

    public function getSelectionBegin(): ?\DateTimeInterface
    {
        return $this->selection_begin;
    }

    public function setSelectionBegin(\DateTimeInterface $selection_begin): self
    {
        $this->selection_begin = $selection_begin;

        return $this;
    }

    public function getSelectionEnd(): ?\DateTimeInterface
    {
        return $this->selection_end;
    }

    public function setSelectionEnd(\DateTimeInterface $selection_end): self
    {
        $this->selection_end = $selection_end;

        return $this;
    }
}
