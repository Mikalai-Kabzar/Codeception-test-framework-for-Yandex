<?php 
$I = new AcceptanceTester($scenario);
$I->wantTo('perform actions and see result');
$I->amOnPage('/');
$I->wait(1);
$I->canSee('Картинки');
$I->canSee('Картинки');
$I->canSee('Картинки2');
$I->canSee('Картинки22');
