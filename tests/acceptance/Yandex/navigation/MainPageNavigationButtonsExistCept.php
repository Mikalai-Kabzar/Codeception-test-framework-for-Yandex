<?php

use Pages\MainPage as MainPage;
 
$I = new YandexTester($scenario);
$I->wantTo('perform actions and see result');
$I->amOnPage('/');
$I->canSee('Картинки');
$I->canSee('Картинки1');




