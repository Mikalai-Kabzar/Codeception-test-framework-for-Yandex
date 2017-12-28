<?php

use Pages\MainPage as MainPage;

class MainPageNavigationButtonsExistCest {

    function _before(YandexTester $I)
    {
        $I->wantTo('Check existence of basic web-element on main page of Yandex.');
        $I->amOnPage('/');
    }

    function checkImagesExists(YandexTester $I)
    {
        $I->seeElement(MainPage::IMAGES_XPATH);
    }

    function checkMapsExists(YandexTester $I)
    {
        $I->seeElement(MainPage::MAPS_XPATH);
    }

    function checkMarketExists(YandexTester $I)
    {
        $I->seeElement(MainPage::MARKET_XPATH);
    }

    function checkMusicExists(YandexTester $I)
    {
        $I->seeElement(MainPage::MUSIC_XPATH);
    }

    function checkSomeWrongWebElementExists(YandexTester $I)
    {
        $I->seeElement('.//a/a/a/a[@class = "some wrong web element"]');
    }

    function checkTranslateExists(YandexTester $I)
    {
        $I->seeElement(MainPage::TRANSLATE_XPATH);
    }

    function checkVideoExists(YandexTester $I)
    {
        $I->seeElement(MainPage::VIDEO_XPATH);
    }

    function checkNewsExists(YandexTester $I)
    {
        $I->seeElement(MainPage::NEWS_XPATH);
    }
}
