#!/usr/bin/env php
<?php
use Magento\Framework\App\Bootstrap;

require '/var/www/magento/app/bootstrap.php';

$users = [
    'nour.abdelhamid' => getenv('PW_NOUR') ?: '',
    'mohamed.elbaz' => getenv('PW_MOHAMED') ?: '',
];

if (!$users['nour.abdelhamid'] || !$users['mohamed.elbaz']) {
    fwrite(STDERR, "Missing PW_NOUR or PW_MOHAMED env vars\n");
    exit(1);
}

$bootstrap = Bootstrap::create(BP, $_SERVER);
$om = $bootstrap->getObjectManager();
/** @var \Magento\User\Model\UserFactory $userFactory */
$userFactory = $om->get(\Magento\User\Model\UserFactory::class);

foreach ($users as $username => $password) {
    /** @var \Magento\User\Model\User $user */
    $user = $userFactory->create()->loadByUsername($username);
    if (!$user->getId()) {
        echo "NOT FOUND: $username\n";
        continue;
    }
    $user->setData('failures_num', 0);
    $user->setData('first_failure', null);
    $user->setData('lock_expires', null);
    $user->setIsActive(1);
    $user->setPassword($password);
    $user->save();
    echo "RESET OK: $username ({$user->getEmail()})\n";
}
