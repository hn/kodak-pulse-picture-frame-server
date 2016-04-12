<?php

/**
 *
 * kodak-pulse-picture-frame-server.php V1.05
 *
 * Kodak Pulse Picture Frame Server (KCS Kodak Cloud Services) Emulator
 *
 * (C) 2010 Hajo Noerenberg
 *
 * http://www.noerenberg.de/
 * https://github.com/hn/kodak-pulse-picture-frame-server
 *
 * Proof-of-concept code, you'll quickly get the idea about how the protocol works.
 *
 * Tested with a W730 model and firmware version '02/23/2010'.
 *
 *
 * KODAK and PULSE are trademarks of Eastman Kodak Company, Rochester, NY 14650-0218.
 *
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 3.0 as
 * published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program. If not, see <http://www.gnu.org/licenses/gpl-3.0.txt>.
 *
 *
 * - Setup
 *
 * 1.) Install the Apache Web Server, listening both on port 80 and 443 (SSL)
 *
 * 2.) Modify Apache'config to redirect all relevant requests to this PHP script. You
 *     can a.) modify the main config file or b.) set up a .htaccess file:
 *
 *     RewriteRule /DeviceRest.* /kodak-pulse-picture-frame-server.php
 *
 * 3.) Re-route all requests for device.pulse.kodak.com to your webserver. You
 *     can choose between two methods:
 *
 *     a.) Insert custom DNS entry for device.pulse.kodak.com in your DSL router:
 *         device.pulse.kodak.com <APACHE IP>
 *
 *     b.) DNAT traffic in your Linux-router:
 *         iptables -t nat -I PREROUTING -d device.pulse.kodak.com -p tcp --dport  80 -j DNAT --to <APACHE IP>
 *         iptables -t nat -I PREROUTING -d device.pulse.kodak.com -p tcp --dport 443 -j DNAT --to <APACHE IP>
 *
 * 4.) Switch on your photo frame.
 *
 *
 * - GUIDs/IDs used in this emulator (you do NOT have to change them!)
 *
 * ba538605-038e-b8ee-02c4-6925cad67189 = 'secret' Kodak API key
 * 55555555-deaf-dead-beef-555555555555 = device (picture frame) activation ID
 * 22222222-1234-5678-9012-123456789012 = user 'admin' profile ID
 * 13333337-1337-1337-1337-424242424242 = session auth token
 * 66666666-5555-3333-2222-222222222222 = user 'collection author' profile ID
 * 77777777-fefe-fefe-fefe-777777777777 = (picture) collection ID
 * 99999999-1111-2222-3333-420000000001 = entity (picture) ID (Example Pic 1)
 * 99999999-1111-2222-3333-420000000002 = entity (picture) ID (Example Pic 2)
 *
 * KCMLP012345678 = frame serial number (printed on the device)
 * NXV123456789 = activation code (printed on package)
 * 123789 = PIN (website activation)
 *
 *
 * - Security 
 *
 * There is a serious security issue with the official Kodak API Server (details
 * are not disclosed here). As of today, I strongly suggest not to
 * upload any personal data to Kodak's server.
 *
 *
 * - Download firmware image
 *
 * curl -v 'http://www.kodak.com/go/update?v=2010.02.23&m=W730&s=KCMLP012345678'
 * curl -v -O 'http://download.kodak.com/digital/software/pictureFrame/autoupdate_test/2010_09_06/Kodak_FW__Fuller.img'
 *
 * Documentation for the IMG file format: http://www.noerenberg.de/hajo/pub/amlogic-firmware-img-file-format.txt
 *
 * - Misc details
 *
 * The picture frame uses Amlogic's proprietary AVOS real-time operating system ('AVOS/1.1 libhttp/1.1'),
 * the MatrixSSL client lib and ZyDAS WLAN.
 *
 */

$r = $_SERVER['REQUEST_URI'];

$e = '<?xml version="1.0" encoding="UTF-8"?' . '>';

if ('/DeviceRest/activate' == $r) {

    /**
     *
     * Step 1: The picture frame connects to https://$deviceActivationURL and
     *         requests activation status and auth URL. Fortunately, the picture
     *         frame does not validate the SSL certificate's hostname.
     *
     * $deviceActivationURL is hardcoded into the firmware and thus
     * cannot be changed (at least, until someone decodes the fw image ;-))
     *
     * curl -v -k -d '<?xml version="1.0"? >
     *     <activationInfo>
     *         <deviceID>KCMLP012345678</deviceID>
     *         <apiVersion>1.0</apiVersion>
     *         <apiKey>ba538605-038e-b8ee-02c4-6925cad67189</apiKey>
     *         <activationCode>NXV123456789</activationCode>
     *     </activationInfo>'
     *     https://device.pulse.kodak.com/DeviceRest/activate
     *
     */


    if (1) { // always activated

        header('HTTP/1.1 412 Precondition Failed');

        print $e . '<activationResponseInfo>' .
                       '<deviceActivationID>55555555-deaf-dead-beef-555555555555</deviceActivationID>' .
                       '<deviceAuthorizationURL>https://device.pulse.kodak.com/DeviceRestV10/Authorize</deviceAuthorizationURL>' .
                       '<deviceProfileList>' .
                           '<admins>' .
                               '<profile>' .
                                   '<id>22222222-1234-5678-9012-123456789012</id>' .
                                   '<name>Firstname Lastname</name>' .
                                   '<emailAddress>firstname.lastname@example.com</emailAddress>' .
                               '</profile>' .
                           '</admins>' .
                       '</deviceProfileList>' .
                   '</activationResponseInfo>';

    } else {

        print $e . '<activationResponseInfo>' .
                       '<deviceActivationID>55555555-deaf-dead-beef-555555555555</deviceActivationID>' .
                       '<deviceAuthorizationURL>https://device.pulse.kodak.com/DeviceRestV10/Authorize</deviceAuthorizationURL>' .
                       '<consumerActivation>' . 
                           '<pin>123789</pin>' .
                           '<url>http://www.kodakpulse.com</url>' .
                       '</consumerActivation>' .
                       '<deviceProfileList><admins /></deviceProfileList>' .
                   '</activationResponseInfo>';

    }

    exit;

} elseif ('/DeviceRestV10/Authorize' == $r) {

    /**
     *
     * Step 2: The picture frame connects to $deviceAuthorizationURL (->Step 1) and
     *         requests auth token and API URL
     *
     * curl -v -k -d '<?xml version="1.0"? >
     *     <authorizationInfo>
     *         <deviceID>KCMLP012345678</deviceID>
     *         <deviceActivationID>55555555-deaf-dead-beef-555555555555</deviceActivationID>
     *         <deviceStorage>
     *             <bytesAvailable>447176504</bytesAvailable>
     *             <bytesTotal>448143360</bytesTotal>
     *             <picturesAvailable>4500</picturesAvailable>
     *             <picturesTotal>4500</picturesTotal>
     *         </deviceStorage>
     *     </authorizationInfo>'
     *     https://device.pulse.kodak.com/DeviceRestV10/Authorize
     *
     */

    if (1) { // always authorized

        print $e . '<authorizationResponseInfo>' .
                       '<authorizationToken>13333337-1337-1337-1337-424242424242</authorizationToken>' .
                       '<apiBaseURL>http://device.pulse.kodak.com/DeviceRestV10</apiBaseURL>' .
                       '<status>' .
                           '<overallStatus>1287525977004</overallStatus>' .
                           '<collectionStatus>1287525977004</collectionStatus>' .
                           '<settingsStatus>1287525781312</settingsStatus>' .
                           '<pollingPeriod>300</pollingPeriod>' .
                       '</status>' .
                       '<deviceProfileList>' .
                           '<admins>' .
                               '<profile>' .
                                   '<id>22222222-1234-5678-9012-123456789012</id>' .
                                   '<name>Firstname Lastname</name>' .
                                   '<emailAddress>firstname.lastname@example.com</emailAddress>' .
                               '</profile>' .
                           '</admins>' .
                       '</deviceProfileList>' .
                   '</authorizationResponseInfo>';

    } else {

        header('HTTP/1.1 400 Bad Request');

    }

    exit;

}

/**
 *
 * Step 3++: The picture frame connects to $apiBaseURL (->Step 2) and
 *           requests device settings, collection status, ...
 *
 * The following functions are only available for picture frames with a
 * valid device (auth) token.
 *
 * curl -v -k -H 'DeviceToken: 13333337-1337-1337-1337-424242424242' <URL>
 *
 *     http://device.pulse.kodak.com/DeviceRestV10/status/0
 *     http://device.pulse.kodak.com/DeviceRestV10/status/1287591702353
 *     http://device.pulse.kodak.com/DeviceRestV10/settings
 *     http://device.pulse.kodak.com/DeviceRestV10/collection
 *     http://device.pulse.kodak.com/DeviceRestV10/profile/66666666-5555-3333-2222-222222222222
 *     http://device.pulse.kodak.com/DeviceRestV10/entity/99999999-1111-2222-3333-420000000001
 *     http://device.pulse.kodak.com/DeviceRestV10/entity/99999999-1111-2222-3333-420000000002
 *
 */

if ('13333337-1337-1337-1337-424242424242' != $_SERVER['HTTP_DEVICETOKEN']) {

    header('HTTP/1.1 424 Failed Dependency');
    exit;

}

if ('/DeviceRestV10/status/' == substr($r, 0, 22)) {

    $s = substr($r, 22);

    if ('1287591702353' != $s) {	// dummy mode: fixed serial, increment on change

        header('HTTP/1.1 425 Unordered Collection');
        print $e . '<status>' .
                       '<overallStatus>1287591702353</overallStatus>' .
                       '<collectionStatus>1287591701461</collectionStatus>' .
                       '<settingsStatus>1287525781312</settingsStatus>' .
                       '<pollingPeriod>300</pollingPeriod>' .
                   '</status>';
    }

} elseif ('/DeviceRestV10/settings' == $r) {

    print $e . '<deviceSettings>' .
                   '<name>My lovely Pulse Frame</name>' .
                   '<slideShowProperties>' .
                       '<duration>10</duration>' .
                       '<transition>FADE</transition>' .
                   '</slideShowProperties>' .
                   '<displayProperties>' . 
                       '<displayMode>ONEUP</displayMode>' .
                       '<showPictureInfo>false</showPictureInfo>' .
                       '<renderMode>FILL</renderMode>' .
                   '</displayProperties>' .
                   '<autoPowerProperties>' .
                       '<autoPowerEnabled>true</autoPowerEnabled>' .
                       '<wakeOnContent>false</wakeOnContent>' .
                       '<autoPowerTime autoType="ON">8:00:00</autoPowerTime>' .
                       '<autoPowerTime autoType="OFF">22:00:00</autoPowerTime>' .
                   '</autoPowerProperties>' .
                   '<defaultCollectionOrder>NAME</defaultCollectionOrder>' .
                   '<respondToLocalControls>true</respondToLocalControls>' .
                   '<language>en-us</language>' .
                   '<timeZoneOffset>0:00:00+2:00</timeZoneOffset>' .
                   '<managePictureStorage>false</managePictureStorage>' .
                   '<logLevel>OFF</logLevel>' .
                   '<enableNotification>true</enableNotification>' .
                   '<modificationDate>2010-10-20T20:18:03Z</modificationDate>' .
                   '<modificationTime>1287605883011</modificationTime>' .
              '</deviceSettings>';

} elseif ('/DeviceRestV10/collection' == $r) {

    print $e . '<collection>' .
                   '<story>' .
                        '<id>77777777-fefe-fefe-fefe-777777777777</id>' .
                        '<title>My Kodak Hacking Session Pics</title>' .
                        '<displayDate>2010-10-19T22:14:30Z</displayDate>' .
                        '<modificationDate>2010-10-19T22:14:31Z</modificationDate>' .
                        '<modificationTime>1287526470836</modificationTime>' .
                        '<authorProfileID>66666666-5555-3333-2222-222222222222</authorProfileID>' .
                        '<source>EMAIL</source>' .
                        '<contents>' .
                            '<pictureSpec>' .
                               '<id>99999999-1111-2222-3333-420000000001</id>' .
                               '<modificationDate>2010-10-19T22:14:31Z</modificationDate>' .
                               '<modificationTime>1287526470727</modificationTime>' .
                            '</pictureSpec>' .
                            '<pictureSpec>' . 
                               '<id>99999999-1111-2222-3333-420000000002</id>' .
                               '<modificationDate>2010-10-19T22:14:24Z</modificationDate>' .
                               '<modificationTime>1287526463446</modificationTime>' .
                            '</pictureSpec>' .
                        '</contents>' .
                   '</story>' .
              '</collection>';

} elseif ('/DeviceRestV10/profile/' == substr($r, 0, 23)) {

    print $e . '<profile>' .
                      '<id>66666666-5555-3333-2222-222222222222</id>' .
                      '<name>Firstname Lastname</name>' .
                      '<emailAddress>firstname.lastname@example.com</emailAddress>' .
                  '</profile>';

} elseif ('/DeviceRestV10/entity/' == substr($r, 0, 22)) {

    // /DeviceRestV10/entity/<id> accepts GET and DELETE

    if ('99999999-1111-2222-3333-420000000001' == substr($r, 22)) {

        print $e . '<picture>' .
                      '<id>99999999-1111-2222-3333-420000000001</id>' .
                      '<title>Hohenzollernbruecke (bridge), Cathedral, Museum Ludwig. Cologne, Germany</title>' .
                      '<captureDate>2004-09-06T16:07:12Z</captureDate>' .
                      '<modificationDate>2010-10-19T22:14:23Z</modificationDate>' .
                      '<modificationTime>1287526463445</modificationTime>' .
                      '<fileURL>http://upload.wikimedia.org/wikipedia/commons/e/ee/Koeln_Hohenzollernbruecke.jpg</fileURL>' .
                  '</picture>';

    } else {

        print $e . '<picture>' .
                      '<id>' . substr($r, 22) . '</id>' .
                      '<title>The Brandenburg Gate in Berlin, Germany</title>' .
                      '<captureDate>2008-05-03T16:07:12Z</captureDate>' .
                      '<modificationDate>2010-10-19T22:14:23Z</modificationDate>' .
                      '<modificationTime>1287526463445</modificationTime>' .
                      '<fileURL>http://upload.wikimedia.org/wikipedia/commons/a/a6/Brandenburger_Tor_abends.jpg</fileURL>' .
                  '</picture>';
    }

} else {

    header('HTTP/1.1 404 Not Found');

}

