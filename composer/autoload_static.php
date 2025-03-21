<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit7f10162a24bb4dafc3c2b5ab17c0388d
{
    public static $prefixLengthsPsr4 = array (
        'o' => 
        array (
            'oliverde8\\AsynchronousJobs\\' => 27,
        ),
        'M' => 
        array (
            'Maniaplanet\\' => 12,
            'ManiaLive\\' => 10,
            'ManiaLivePlugins\\eXpansion\\' => 27,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'oliverde8\\AsynchronousJobs\\' => 
        array (
            0 => __DIR__ . '/..' . '/oliverde8/asynchronous-jobs/src/AsynchronousJobs',
        ),
        'Maniaplanet\\' => 
        array (
            0 => __DIR__ . '/..' . '/maniaplanet/dedicated-server-api/libraries/Maniaplanet',
            1 => __DIR__ . '/..' . '/maniaplanet/maniaplanet-ws-sdk/libraries/Maniaplanet',
        ),
        'ManiaLive\\' => 
        array (
            0 => __DIR__ . '/..' . '/maniaplanet/manialive-lib/ManiaLive',
        ),
        'ManiaLivePlugins\\eXpansion\\' => 
        array (
            0 => __DIR__ . '/..' . '/ml-expansion/expansion',
        ),
    );

    public static $prefixesPsr0 = array (
        'P' => 
        array (
            'Phine\\Phar' => 
            array (
                0 => __DIR__ . '/..' . '/phine/phar/src/lib',
            ),
            'Phine\\Path' => 
            array (
                0 => __DIR__ . '/..' . '/phine/path/src/lib',
            ),
            'Phine\\Observer' => 
            array (
                0 => __DIR__ . '/..' . '/phine/observer/src/lib',
            ),
            'Phine\\Exception' => 
            array (
                0 => __DIR__ . '/..' . '/phine/exception/src/lib',
            ),
        ),
        'M' => 
        array (
            'Monolog' => 
            array (
                0 => __DIR__ . '/..' . '/monolog/monolog/src',
            ),
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
        'ManiaLib\\Application\\ActionNotFoundException' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/Controller.php',
        'ManiaLib\\Application\\AdvancedFilter' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/AdvancedFilter.php',
        'ManiaLib\\Application\\Bootstrapper' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/Bootstrapper.php',
        'ManiaLib\\Application\\Config' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/Config.php',
        'ManiaLib\\Application\\ConfigLoader' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/ConfigLoader.php',
        'ManiaLib\\Application\\Controller' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/Controller.php',
        'ManiaLib\\Application\\ControllerNotFoundException' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/Controller.php',
        'ManiaLib\\Application\\DialogHelper' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/DialogHelper.php',
        'ManiaLib\\Application\\Dispatcher' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/Dispatcher.php',
        'ManiaLib\\Application\\ErrorHandling' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/ErrorHandling.php',
        'ManiaLib\\Application\\Exception' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/Exception.php',
        'ManiaLib\\Application\\Filterable' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/Filterable.php',
        'ManiaLib\\Application\\Filters\\UserAgentCheck' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/Filters/UserAgentCheck.php',
        'ManiaLib\\Application\\Rendering\\Manialink' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/Rendering/Manialink.php',
        'ManiaLib\\Application\\Rendering\\RendererInterface' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/Rendering/RendererInterface.php',
        'ManiaLib\\Application\\Rendering\\SimpleTemplates' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/Rendering/SimpleTemplates.php',
        'ManiaLib\\Application\\Rendering\\ViewNotFoundException' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/Rendering/ViewNotFoundException.php',
        'ManiaLib\\Application\\Request' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/Request.php',
        'ManiaLib\\Application\\Response' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/Response.php',
        'ManiaLib\\Application\\Route' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/Route.php',
        'ManiaLib\\Application\\Session' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/Session.php',
        'ManiaLib\\Application\\SilentUserException' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/SilentUserException.php',
        'ManiaLib\\Application\\Tracking\\Config' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/Tracking/Config.php',
        'ManiaLib\\Application\\Tracking\\EventTracker' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/Tracking/EventTracker.php',
        'ManiaLib\\Application\\Tracking\\Filter' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/Tracking/Filter.php',
        'ManiaLib\\Application\\Tracking\\GoogleAnalytics' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/Tracking/GoogleAnalytics.php',
        'ManiaLib\\Application\\Tracking\\View' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/Tracking/View.php',
        'ManiaLib\\Application\\UserException' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/UserException.php',
        'ManiaLib\\Application\\View' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/View.php',
        'ManiaLib\\Application\\Views\\Dialogs\\DialogInterface' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/Views/Dialogs/DialogInterface.php',
        'ManiaLib\\Application\\Views\\Dialogs\\OneButton' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/Views/Dialogs/OneButton.php',
        'ManiaLib\\Application\\Views\\Dialogs\\TwoButtons' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/Views/Dialogs/TwoButtons.php',
        'ManiaLib\\Application\\Views\\Error' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/Views/Error.php',
        'ManiaLib\\Application\\Views\\Footer' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/Views/Footer.php',
        'ManiaLib\\Application\\Views\\Header' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Application/Views/Header.php',
        'ManiaLib\\Cache\\Cache' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Cache/Cache.php',
        'ManiaLib\\Cache\\CacheInterface' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Cache/CacheInterface.php',
        'ManiaLib\\Cache\\Config' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Cache/Config.php',
        'ManiaLib\\Cache\\Drivers\\APC' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Cache/Drivers/APC.php',
        'ManiaLib\\Cache\\Drivers\\Exception' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Cache/Drivers/Exception.php',
        'ManiaLib\\Cache\\Drivers\\Memcache' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Cache/Drivers/Memcache.php',
        'ManiaLib\\Cache\\Drivers\\MemcacheConfig' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Cache/Drivers/MemcacheConfig.php',
        'ManiaLib\\Cache\\Drivers\\MemcacheConnectionParams' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Cache/Drivers/MemcacheConnectionParams.php',
        'ManiaLib\\Cache\\Drivers\\Memcached' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Cache/Drivers/Memcached.php',
        'ManiaLib\\Cache\\Drivers\\NoCache' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Cache/Drivers/NoCache.php',
        'ManiaLib\\Cache\\Exception' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Cache/Exception.php',
        'ManiaLib\\Gui\\Cards\\Data' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Cards/Data.php',
        'ManiaLib\\Gui\\Cards\\DatePicker' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Cards/DatePicker.php',
        'ManiaLib\\Gui\\Cards\\Dialogs\\OneButton' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Cards/Dialogs/OneButton.php',
        'ManiaLib\\Gui\\Cards\\Dialogs\\TwoButtons' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Cards/Dialogs/TwoButtons.php',
        'ManiaLib\\Gui\\Cards\\Navigation\\Button' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Cards/Navigation/Button.php',
        'ManiaLib\\Gui\\Cards\\Navigation\\Config' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Cards/Navigation/Config.php',
        'ManiaLib\\Gui\\Cards\\Navigation\\Menu' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Cards/Navigation/Menu.php',
        'ManiaLib\\Gui\\Cards\\PageNavigator' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Cards/PageNavigator.php',
        'ManiaLib\\Gui\\Cards\\Panel' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Cards/Panel.php',
        'ManiaLib\\Gui\\Component' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Component.php',
        'ManiaLib\\Gui\\Drawable' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Drawable.php',
        'ManiaLib\\Gui\\Element' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Element.php',
        'ManiaLib\\Gui\\Elements\\Audio' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Elements/Audio.php',
        'ManiaLib\\Gui\\Elements\\BgRaceScore2' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Elements/BgRaceScore2.php',
        'ManiaLib\\Gui\\Elements\\Bgs1' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Elements/Bgs1.php',
        'ManiaLib\\Gui\\Elements\\Bgs1InRace' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Elements/Bgs1InRace.php',
        'ManiaLib\\Gui\\Elements\\BgsChallengeMedals' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Elements/BgsChallengeMedals.php',
        'ManiaLib\\Gui\\Elements\\BgsPlayerCard' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Elements/BgsPlayerCard.php',
        'ManiaLib\\Gui\\Elements\\Button' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Elements/Button.php',
        'ManiaLib\\Gui\\Elements\\Copilot' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Elements/Copilot.php',
        'ManiaLib\\Gui\\Elements\\Entry' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Elements/Entry.php',
        'ManiaLib\\Gui\\Elements\\FileEntry' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Elements/FileEntry.php',
        'ManiaLib\\Gui\\Elements\\Format' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Elements/Format.php',
        'ManiaLib\\Gui\\Elements\\Frame' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Elements/Frame.php',
        'ManiaLib\\Gui\\Elements\\Frame3d' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Elements/Frame3d.php',
        'ManiaLib\\Gui\\Elements\\Frame3dStyles' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Elements/Frame3dStyles.php',
        'ManiaLib\\Gui\\Elements\\Icon' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Elements/Icon.php',
        'ManiaLib\\Gui\\Elements\\Icons128x128_1' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Elements/Icons128x128_1.php',
        'ManiaLib\\Gui\\Elements\\Icons128x128_Blink' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Elements/Icons128x128_Blink.php',
        'ManiaLib\\Gui\\Elements\\Icons128x32_1' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Elements/Icons128x32_1.php',
        'ManiaLib\\Gui\\Elements\\Icons321Go' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Elements/Icons321Go.php',
        'ManiaLib\\Gui\\Elements\\Icons64x64_1' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Elements/Icons64x64_1.php',
        'ManiaLib\\Gui\\Elements\\IncludeManialink' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Elements/IncludeManialink.php',
        'ManiaLib\\Gui\\Elements\\Label' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Elements/Label.php',
        'ManiaLib\\Gui\\Elements\\ManiaPlanetLogos' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Elements/ManiaPlanetLogos.php',
        'ManiaLib\\Gui\\Elements\\MedalsBig' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Elements/MedalsBig.php',
        'ManiaLib\\Gui\\Elements\\Music' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Elements/Music.php',
        'ManiaLib\\Gui\\Elements\\Quad' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Elements/Quad.php',
        'ManiaLib\\Gui\\Elements\\Spacer' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Elements/Spacer.php',
        'ManiaLib\\Gui\\Elements\\Style3d' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Elements/Style3d.php',
        'ManiaLib\\Gui\\Elements\\Stylesheet' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Elements/Stylesheet.php',
        'ManiaLib\\Gui\\Elements\\UIConstructionSimple_Buttons' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Elements/UIConstructionSimple_Buttons.php',
        'ManiaLib\\Gui\\Elements\\Video' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Elements/Video.php',
        'ManiaLib\\Gui\\Layouts\\AbstractLayout' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Layouts/AbstractLayout.php',
        'ManiaLib\\Gui\\Layouts\\Column' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Layouts/Column.php',
        'ManiaLib\\Gui\\Layouts\\Flow' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Layouts/Flow.php',
        'ManiaLib\\Gui\\Layouts\\Line' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Layouts/Line.php',
        'ManiaLib\\Gui\\Layouts\\Spacer' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Layouts/Spacer.php',
        'ManiaLib\\Gui\\Layouts\\VerticalFlow' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Layouts/VerticalFlow.php',
        'ManiaLib\\Gui\\Maniacode\\Component' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Maniacode/Component.php',
        'ManiaLib\\Gui\\Maniacode\\Elements\\AddBuddy' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Maniacode/Elements/AddBuddy.php',
        'ManiaLib\\Gui\\Maniacode\\Elements\\AddFavourite' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Maniacode/Elements/AddFavourite.php',
        'ManiaLib\\Gui\\Maniacode\\Elements\\FileDownload' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Maniacode/Elements/FileDownload.php',
        'ManiaLib\\Gui\\Maniacode\\Elements\\GetSkin' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Maniacode/Elements/GetSkin.php',
        'ManiaLib\\Gui\\Maniacode\\Elements\\GotoLink' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Maniacode/Elements/GotoLink.php',
        'ManiaLib\\Gui\\Maniacode\\Elements\\InstallMacroblock' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Maniacode/Elements/InstallMacroblock.php',
        'ManiaLib\\Gui\\Maniacode\\Elements\\InstallMap' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Maniacode/Elements/InstallMap.php',
        'ManiaLib\\Gui\\Maniacode\\Elements\\InstallMapPack' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Maniacode/Elements/InstallMapPack.php',
        'ManiaLib\\Gui\\Maniacode\\Elements\\InstallPack' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Maniacode/Elements/InstallPack.php',
        'ManiaLib\\Gui\\Maniacode\\Elements\\InstallReplay' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Maniacode/Elements/InstallReplay.php',
        'ManiaLib\\Gui\\Maniacode\\Elements\\InstallSkin' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Maniacode/Elements/InstallSkin.php',
        'ManiaLib\\Gui\\Maniacode\\Elements\\InstallTrack' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Maniacode/Elements/InstallTrack.php',
        'ManiaLib\\Gui\\Maniacode\\Elements\\InstallTrackPack' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Maniacode/Elements/InstallTrackPack.php',
        'ManiaLib\\Gui\\Maniacode\\Elements\\InviteBuddy' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Maniacode/Elements/InviteBuddy.php',
        'ManiaLib\\Gui\\Maniacode\\Elements\\JoinServer' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Maniacode/Elements/JoinServer.php',
        'ManiaLib\\Gui\\Maniacode\\Elements\\PackageMap' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Maniacode/Elements/PackageMap.php',
        'ManiaLib\\Gui\\Maniacode\\Elements\\PackageTrack' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Maniacode/Elements/PackageTrack.php',
        'ManiaLib\\Gui\\Maniacode\\Elements\\PlayReplay' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Maniacode/Elements/PlayReplay.php',
        'ManiaLib\\Gui\\Maniacode\\Elements\\PlayTrack' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Maniacode/Elements/PlayTrack.php',
        'ManiaLib\\Gui\\Maniacode\\Elements\\ShowMessage' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Maniacode/Elements/ShowMessage.php',
        'ManiaLib\\Gui\\Maniacode\\Elements\\ViewReplay' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Maniacode/Elements/ViewReplay.php',
        'ManiaLib\\Gui\\Maniacode\\Maniacode' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Maniacode/Maniacode.php',
        'ManiaLib\\Gui\\Manialink' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Manialink.php',
        'ManiaLib\\Gui\\Tools' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Gui/Tools.php',
        'ManiaLib\\ManiaScript\\Action' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/ManiaScript/Action.php',
        'ManiaLib\\ManiaScript\\Config' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/ManiaScript/Config.php',
        'ManiaLib\\ManiaScript\\Event' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/ManiaScript/Event.php',
        'ManiaLib\\ManiaScript\\Main' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/ManiaScript/Main.php',
        'ManiaLib\\ManiaScript\\Manipulation' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/ManiaScript/Manipulation.php',
        'ManiaLib\\ManiaScript\\Tools' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/ManiaScript/Tools.php',
        'ManiaLib\\ManiaScript\\UI' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/ManiaScript/UI.php',
        'ManiaLib\\ManiaScript\\VersionCheck' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/ManiaScript/VersionCheck.php',
        'ManiaLib\\Utils\\Arrays' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Utils/Arrays.php',
        'ManiaLib\\Utils\\Color' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Utils/Color.php',
        'ManiaLib\\Utils\\FileTooLargeException' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Utils/Upload.php',
        'ManiaLib\\Utils\\Formatting' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Utils/Formatting.php',
        'ManiaLib\\Utils\\KnilToken' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Utils/StyleParser.php',
        'ManiaLib\\Utils\\LinkToken' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Utils/StyleParser.php',
        'ManiaLib\\Utils\\Logger' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Utils/Logger.php',
        'ManiaLib\\Utils\\LoggerConfig' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Utils/LoggerConfig.php',
        'ManiaLib\\Utils\\MultipageList' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Utils/MultipageList.php',
        'ManiaLib\\Utils\\Path' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Utils/Path.php',
        'ManiaLib\\Utils\\Singleton' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Utils/Singleton.php',
        'ManiaLib\\Utils\\StyleParser' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Utils/StyleParser.php',
        'ManiaLib\\Utils\\TextToken' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Utils/StyleParser.php',
        'ManiaLib\\Utils\\URI' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Utils/URI.php',
        'ManiaLib\\Utils\\Upload' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Utils/Upload.php',
        'ManiaLib\\Utils\\UserAgent' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Utils/UserAgent.php',
        'ManiaLib\\Version' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/Version.php',
        'ManiaLib\\WebServices\\Config' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/WebServices/Config.php',
        'ManiaLib\\WebServices\\ManiaConnectFilter' => __DIR__ . '/..' . '/maniaplanet/manialib/libraries/ManiaLib/WebServices/ManiaConnectFilter.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit7f10162a24bb4dafc3c2b5ab17c0388d::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit7f10162a24bb4dafc3c2b5ab17c0388d::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInit7f10162a24bb4dafc3c2b5ab17c0388d::$prefixesPsr0;
            $loader->classMap = ComposerStaticInit7f10162a24bb4dafc3c2b5ab17c0388d::$classMap;

        }, null, ClassLoader::class);
    }
}
