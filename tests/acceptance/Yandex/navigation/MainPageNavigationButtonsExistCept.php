<?php

use Pages\MainPage as MainPage;

$I = new YandexTester($scenario);
$I->wantTo('Check existence of basic web-element on main page of Yandex.');
$I->amOnPage('/');
$I->seeElement(MainPage::IMAGES_XPATH);
$I->seeElement(MainPage::MAPS_XPATH);
$I->seeElement(MainPage::MARKET_XPATH);
$I->seeElement(MainPage::MUSIC_XPATH);
$I->seeElement(MainPage::TRANSLATE_XPATH);
$I->seeElement(MainPage::VIDEO_XPATH);
$I->seeElement(MainPage::NEWS_XPATH);
