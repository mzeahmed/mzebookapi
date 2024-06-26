<?php

declare(strict_types=1);

namespace App\DataFixtures;

use Faker\Generator;
use App\Entity\User;
use App\Entity\Usermeta;
use Doctrine\Persistence\ObjectManager;

class UserFixtures
{
    private ObjectManager $manager;
    private Generator $faker;

    public function __construct(ObjectManager $manager, Generator $faker)
    {
        $this->manager = $manager;
        $this->faker = $faker;
    }

    public function load($hasher): void
    {
        foreach ($this->getUserData() as [$login, $email, $password, $twoFaToken]) {
            $firstname = $this->faker->firstName;
            $lastname = $this->faker->lastName;

            if (empty($login)) {
                $login = strtolower($firstname) . strtolower($lastname);
            }

            if (!empty($login)) {
                $firstname = ucfirst($login);
                $lastname = ucfirst($login);
            }

            $user = new User();
            $user->setLogin($login);
            $user->setEmail($email);
            $user->setPassword($hasher->hash($password));
            $user->setTwoFaToken($twoFaToken);
            $user->setCreatedAt(new \DateTimeImmutable());

            $usermeta = new Usermeta();
            $usermeta
                ->setUser($user)
                ?->setMetaKey(USERMETA_FIRST_NAME)
                ->setMetaValue($firstname);
            $user->addUsermeta($usermeta);

            $usermeta = new Usermeta();
            $usermeta
                ->setUser($user)
                ?->setMetaKey(USERMETA_LAST_NAME)
                ->setMetaValue($lastname);
            $user->addUsermeta($usermeta);

            $usermeta = new Usermeta();
            $usermeta
                ->setUser($user)
                ?->setMetaKey(USERMETA_BIRTHDATE)
                ->setMetaValue($this->faker->date('Y-m-d H:i:s'));
            $user->addUsermeta($usermeta);

            $this->manager->persist($user);
        }

        $this->manager->flush();
    }

    private function getUserData(): array
    {
        $users = [];

        foreach ($this->generateUsers() as $user) {
            $users[] = [
                $user['login'] ?? '',
                $user['email'],
                $user['password'],
                $user['twoFaToken'],
            ];
        }

        return $users;
    }

    private function generateUsers(): array
    {
        $users = [
            [
                'login' => 'admin',
                'email' => 'admin@localhost',
                'password' => 'password',
                'twoFaToken' => $this->faker->uuid,
            ],
            [
                'login' => 'user',
                'email' => 'user@localhost',
                'password' => 'password',
                'twoFaToken' => $this->faker->uuid,
            ],
        ];

        for ($i = 0; $i < 500; $i ++) {
            $users[] = [
                'email' => $this->faker->unique()->email,
                'password' => 'password',
                'twoFaToken' => $this->faker->uuid,
            ];
        }

        return $users;
    }
}
