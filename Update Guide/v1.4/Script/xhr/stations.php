<?php
if (IS_LOGGED == false) {
    exit("You ain't logged in!");
}
if ($option == 'get_stations') {
    if (empty($_POST['keyword'])) {
        exit();
    }
    $keyword = secure($_POST['keyword']);
    $data['status'] = 200;
    $stations = GetRadioStations($keyword);
    $record_count = 0;
    $html = '<div class="no-track-found bg_light"><svg height="512" viewBox="0 0 60 60" width="512" xmlns="http://www.w3.org/2000/svg"><g id="Page-1" fill="currentColor" fill-rule="evenodd"><g id="008---Radio" fill="rgb(0,0,0)" fill-rule="nonzero" transform="translate(-1)"><path fill="currentColor" id="Shape" d="m31 41.54-4.71 3.13 4.71 1.47 4.71-1.47z"></path><path fill="currentColor" id="Shape" d="m31 51.95 5.74-1.92-5.74-1.79-5.74 1.79z"></path><path fill="currentColor" id="Shape" d="m38.138 38.433c.1890241.1863264.4443684.2898625.7097801.2877992s.5191154-.1095566.7052199-.2987992c4.6310411-4.7058584 4.5906399-12.2688196-.0904128-16.9249329-4.6810527-4.6561134-12.2441217-4.6561134-16.9251744 0-4.6810527 4.6561133-4.7214539 12.2190745-.0904128 16.9249329.3910651.367396 1.0023888.3606722 1.3852785-.0152363s.4008601-.9870051.0407215-1.3847637c-3.8596105-3.9214057-3.8262409-10.2241334.0746755-14.1044513 3.9009165-3.8803178 10.2037325-3.8803178 14.104649 0 3.9009164 3.8803179 3.934286 10.1830456.0746755 14.1044513-.3853619.3931749-.380445 1.023881.011 1.411z"></path><path fill="currentColor" id="Shape" d="m41.411 44.44c.2217496.0004433.4372408-.0734997.612-.21 6.0842634-4.7130528 8.4989854-12.7728945 6.0084468-20.0549509s-9.3352706-12.1750692-17.0314468-12.1750692-14.5409082 4.8930128-17.0314468 12.1750692-.0758166 15.3418981 6.0084468 20.0549509c.2806177.2378475.6691625.302426 1.0116313.1681392.3424687-.1342867.5835605-.4457545.6277166-.8109503s-.1157538-.725149-.4163479-.9371889c-5.4088636-4.1890317-7.5559052-11.3535235-5.342314-17.8268383 2.2135913-6.4733149 8.2979843-10.8230151 15.139314-10.8230151s12.9257227 4.3497002 15.139314 10.8230151c2.2135912 6.4733148.0665496 13.6378066-5.342314 17.8268383-.3377747.2621414-.47152.7100284-.3327933 1.1144598.1387268.4044315.5192307.6759223.9467933.6755402z"></path><path fill="currentColor" id="Shape" d="m43.921 50.03c.2003247.0002752.3959909-.0604127.561-.174 8.7184311-5.9199045 12.5551169-16.8346318 9.4584539-26.9077141-3.0966629-10.0730824-12.4021281-16.94765485-22.9404539-16.94765485s-19.843791 6.87457245-22.94045395 16.94765485c-3.09666296 10.0730823.74002286 20.9878096 9.45845395 26.9077141.4572918.3106601 1.0798398.1917917 1.3904999-.2655.3106602-.4572918.1917918-1.0798398-.2654999-1.3905-7.9926195-5.4261086-11.51034146-15.4314606-8.67201582-24.6655565 2.83832562-9.234096 11.36854892-15.53621679 21.02901582-15.53621679s18.1906902 6.30212079 21.0290158 15.53621679c2.8383257 9.2340959-.6793963 19.2394479-8.6720158 24.6655565-.3633259.2469906-.5228956.7021872-.3933077 1.1219694.129588.4197821.5179793.7058216.9573077.7050306z"></path><path fill="currentColor" id="Shape" d="m31 0c-13.3810514-.00550229-25.14792834 8.85160355-28.84472764 21.7118618-3.69679929 12.8602582 1.57039241 26.6139635 12.91172764 33.7151382.302604.1902439.6837155.2045993.9997735.0376585.3160581-.1669407.5190459-.4898153.5325-.847.0134541-.3571846-.1646695-.6944146-.4672735-.8846585-10.5793892-6.6295049-15.49136857-19.4623106-12.04240232-31.4614124 3.44896624-11.9991017 14.42545892-20.26499854 26.91040232-20.26499854s23.4614361 8.26589684 26.9104023 20.26499854c3.4489663 11.9991018-1.4630131 24.8319075-12.0424023 31.4614124-.4677852.2940916-.6085916.9117148-.3145 1.3795.2940917.4677851.9117148.6085916 1.3795.3145 11.3413352-7.1011747 16.6085269-20.85488 12.9117276-33.7151382-3.6967993-12.86025825-15.4636762-21.71736409-28.8447276-21.7118618z"></path><path fill="currentColor" id="Shape" d="m33.542 33.231c-.0223633-.0368103-.0474324-.071907-.075-.105 1.343049-1.0522974 1.8707718-2.8406649 1.3141735-4.4535223-.5565984-1.6128574-2.0749758-2.6951141-3.7811735-2.6951141s-3.2245751 1.0822567-3.7811735 2.6951141c-.5565983 1.6128574-.0288755 3.4012249 1.3141735 4.4535223-.0275676.033093-.0526367.0681897-.075.105l-11.37 25.36c-.1595593.3279594-.1279169.7167773.082571 1.0146219s.5664356.4574746.9288472.4165553.6738047-.2758977.8125818-.6131772l1.81-4.036 7.118-2.373-5.268-1.753 1.285-2.868 3.793-1.189-2.859-.89 1.931-4.306 2.478-1.654-1.337-.9.826-1.843 2.311 1.543 2.311-1.54.826 1.843-1.337.9 2.478 1.65 1.931 4.307-2.859.894 3.793 1.189 1.285 2.868-5.268 1.749 7.118 2.373 1.81 4.036c.1387771.3372795.4501702.5722579.8125818.6131772s.7183593-.1187107.9288472-.4165553.2421303-.6866625.082571-1.0146219z"></path></g></g></svg>' . lang("No stations found") . '</div>';
    if (!empty($stations)) {
        $html = "";
        $my_stations = $db->arrayBuilder()->where('user_id', $music->user->id)->where('src', 'radio')->get(T_SONGS,null,array('lyrics'));
        $stations_array = array();
        foreach ($my_stations as $key => $value){
            $stations_array[] = $value['lyrics'];
        }
        foreach ($stations as $key => $station) {
            if(!in_array($station['radio_id'],$stations_array)) {
                $record_count++;
                $key = ($key + 1);
                $html .= loadPage("user/station", [
                    'STATION_DATA' => $station
                ]);
            }
        }
    }
    $data['html'] = $html;
}
if ($option == 'add_stations') {
    $data['status'] = 400;
    $checkIfStationExits = $db->where('user_id', $music->user->id)->where('lyrics', secure($_POST['id']))->where('src', 'radio')->getValue(T_SONGS, 'count(*)');
    if (empty($checkIfStationExits)) {

        $audio_id        = generateKey(15, 15);
        $check_for_audio = $db->where('audio_id', $audio_id)->getValue(T_SONGS, 'count(*)');
        if ($check_for_audio > 0) {
            $audio_id = generateKey(15, 15);
        }

        if (!file_exists('upload/photos/' . date('Y'))) {
            @mkdir('upload/photos/' . date('Y'), 0777, true);
        }
        if (!file_exists('upload/photos/' . date('Y') . '/' . date('m'))) {
            @mkdir('upload/photos/' . date('Y') . '/' . date('m'), 0777, true);
        }
        $dir = "upload/photos/" . date('Y') . '/' . date('m');
        $filename    = $dir . '/' . generateKey() . '_' . date('d') . '_' . md5(time()) . "_image.jpg";
        $file_data = file_get_contents(secure($_POST['logo']));
        file_put_contents($filename, $file_data);
        if (($music->config->s3_upload == 'on' || $music->config->ftp_upload == 'on' || $music->config->spaces == 'on') && !empty($filename)) {
            $upload_s3 = PT_UploadToS3($filename);
        }
        $data_insert = array(
            'audio_id' => $audio_id,
            'user_id' => $music->user->id,
            'title' => secure($_POST['station']),
            'description' => secure($_POST['country']),
            'lyrics' => secure($_POST['id']),
            'tags' => secure($_POST['genre']),
            'duration' => '',
            'audio_location' => secure($_POST['url']),
            'category_id' => '',
            'thumbnail' => $filename,
            'time' => time(),
            'registered' => date('Y') . '/' . intval(date('m')),
            'size' => 0,
            'availability' => 0,
            'age_restriction' => 0,
            'price' => 0,
            'spotlight' => 0,
            'ffmpeg' => 0,
            'allow_downloads' => 0,
            'display_embed' => 0,
            'src' => 'radio'
        );
        $addStation = $db->insert(T_SONGS, $data_insert);
        if ($addStation) {
            $create_activity = createActivity([
                'user_id' => $music->user->id,
                'type' => 'uploaded_track',
                'track_id' => $insert,
            ]);
            $data_insert['id'] = $insert;
            notifyUploadTrack($data_insert);

            $data = array(
                'status' => 200,
                'audio_id' => $audio_id,
                'song_location' => secure($_POST['url']),
                'link' => getLink("track/$audio_id")
            );
        }
    }
}