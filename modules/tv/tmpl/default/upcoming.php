<?php
/**
 * Show all upcoming recordings.
 *
 * @url         $URL$
 * @date        $Date$
 * @version     $Revision$
 * @author      $Author$
 * @license     GPL
 *
 * @package     MythWeb
 * @subpackage  TV
 *
/**/

// Set the desired page title
    $page_title = 'MythWeb - '.t('Upcoming Recordings');

// Custom headers
    $headers[] = '<link rel="stylesheet" type="text/css" href="'.skin_url.'/tv_upcoming.css" />';

// Print the page header
    require 'modules/_shared/tmpl/'.tmpl.'/header.php';

/** @todo FIXME:  pull this out of the theme page! */
// Which field are we grouping by?
    $group_field = $_SESSION['scheduled_sortby'][0]['field'];
    if (empty($group_field)) {
        $group_field = "airdate";
    }
    elseif (!in_array($group_field, array('title', 'channum', 'airdate'))) {
        $group_field = '';
    }

?>

<form id="change_display" action="<?php echo root ?>tv/upcoming" method="post">
<input type="hidden" name="change_display" value="1">

<table id="display_options" class="commandbox commands" border="0" cellspacing="0" cellpadding="0">
<tr>
    <td class="-title"><?php echo t('Display') ?>:</td>
    <td class="-check">
        <label for="disp_scheduled">
        <input type="checkbox" id="disp_scheduled" name="disp_scheduled" class="radio" onclick="$('change_display').submit()"<?php
            if ($_SESSION['scheduled_recordings']['disp_scheduled']) echo ' CHECKED' ?>>
        <?php echo t('Scheduled') ?></label>
        </td>
    <td class="-check">
        <label for="disp_duplicates">
        <input type="checkbox" id="disp_duplicates" name="disp_duplicates" class="radio" onclick="$('change_display').submit()" <?php
            if ($_SESSION['scheduled_recordings']['disp_duplicates']) echo ' CHECKED' ?>>
        <?php echo t('Duplicates') ?></label>
        </td>
    <td class="-check">
        <label for="disp_deactivated">
        <input type="checkbox" id="disp_deactivated" name="disp_deactivated" class="radio" onclick="$('change_display').submit()" <?php
            if ($_SESSION['scheduled_recordings']['disp_deactivated']) echo ' CHECKED' ?>>
        <?php echo t('Deactivated') ?></label>
        </td>
    <td class="-check">
        <label for="disp_conflicts">
        <input type="checkbox" id="disp_conflicts" name="disp_conflicts" class="radio" onclick="$('change_display').submit()" <?php
            if ($_SESSION['scheduled_recordings']['disp_conflicts']) echo ' CHECKED' ?>>
        <?php echo t('Conflicts') ?></label>
        </td>
</tr>
</table>

</form>

<table id="listings" border="0" cellpadding="4" cellspacing="2" class="list small">
<tr class="menu">
    <?php if ($group_field != '') echo "<td class=\"list\">&nbsp;</td>\n" ?>
    <th class="-status"><?php  echo t('Status') ?></td>
    <th class="-title"><?php   echo get_sort_link('title',   t('Title'))   ?></th>
    <th class="-channum"><?php echo get_sort_link('channum', t('Channel')) ?></th>
    <th class="-airdate"><?php echo get_sort_link('airdate', t('Airdate')) ?></th>
    <th class="-length"><?php  echo get_sort_link('length',  t('Length'))  ?></th>
</tr><?php
    $row = 0;

    $prev_group = '';
    $cur_group  = '';
    foreach ($all_shows as $show) {
    // Set the class to be used to display the recording status character
        $rec_class = implode(' ', array(recstatus_class($show), $show->recstatus));
    // Reset the command variable to a default URL
        $commands = array();
        $urlstr = 'chanid='.$show->chanid.'&starttime='.$show->starttime;
    // Set the recording status character, class and any applicable commands for each show
        switch ($show->recstatus) {
            case 'Recording':
            case 'WillRecord':
                $rec_char   = $show->inputname;
                $css_class  = 'scheduled';
                $commands[] = 'dontrec';
            // Offer to suppress any recordings that have enough info to do so.
                if (preg_match('/\\S/', $show->title)
                        && (preg_match('/\\S/', $show->programid.$show->subtitle.$show->description))) {
                    $commands[] = 'never_record';
                }
                break;
            case 'PreviousRecording':
                $rec_char   = t('Duplicate');
                $css_class  = 'duplicate';
                $commands[] = 'record';
                $commands[] = 'forget_old';
                break;
            case 'CurrentRecording':
                $rec_char   = t('Recorded');
                $css_class  = 'duplicate';
                $commands[] = 'record';
                $commands[] = 'forget_old';
                break;
            case 'Repeat':
                $rec_char   = 'Rerun';
                $css_class  = 'duplicate';
                $commands[] = 'record';
                break;
            case 'EarlierShowing':
                $rec_char = t('Earlier');
                $css_class= 'deactivated';
                $commands[] = 'activate';
                $commands[] = 'default';
                break;
            case 'TooManyRecordings':
                $rec_char = t('Too Many');
                $css_class= 'deactivated';
                break;
            case 'Cancelled':
                $rec_char   = t('Cancelled');
                $css_class  = 'deactivated';
                $commands[] = 'activate';
                $commands[] = 'default';
                break;
            case 'Conflict':
                $rec_char = t('Conflict');
            // We normally use the recstatus value as the name of the class
            //  used when displaying the recording status character.
            // However, there is already a class named 'conflict' so we
            //  need to modify this specific recstatus to avoid a conflict.
                $rec_class = implode(' ', array(recstatus_class($show),
                                     'conflicting'));
                $css_class  = 'conflict';
                $commands[] = 'record';
                $commands[] = 'dontrec';
                break;
            case 'LaterShowing':
                $rec_char = t('Later');
                $css_class= 'deactivated';
                $commands[] = 'activate';
                $commands[] = 'default';
                break;
            case 'LowDiskSpace':
                $rec_char   = t('Low Space');
                $css_class  = 'deactivated';
                $commands[] = 'Not Enough Disk Space';
                break;
            case 'TunerBusy':
                $rec_char   = t('Tuner Busy');
                $css_class  = 'deactivated';
                $commands[] = 'Tuner is busy';
                break;
            case 'Overlap':
                $rec_char   = t('Override');
                $css_class  = 'conflict';
                $commands[] = 'record';
                $commands[] = 'dontrec';
                break;
            case 'ManualOverride':
                $rec_char   = t('Override');
                $css_class  = 'deactivated';
                $commands[] = 'activate';
                $commands[] = 'default';
                break;
            case 'ForceRecord':
                $rec_char   = $show->inputname ? $show->inputname : t('Forced');
                $css_class  = 'scheduled';
                $commands[] = 'dontrec';
                $commands[] = 'default';
                break;
            default:
                $rec_char   = '&nbsp;';
                $rec_class  = '';
                $css_class  = 'deactivated';
                $commands[] = 'activate';
                $commands[] = 'dontrec';
                break;
        }
    // Now do the necessary replacements for each command
        foreach ($commands as $key => $val) {
            switch ($val) {
                case 'dontrec':
                    $commands[$key] = '<a href="'.root.'tv/upcoming?dontrec=yes&'.$urlstr.'"'
                                     .' title="'.html_entities(t('info: dont record')).'">'
                                     .t('Don\'t Record').'</a>';
                    break;
                case 'never_record':
                    $commands[$key] = '<a href="'.root.'tv/upcoming?never_record=yes&'.$urlstr.'"'
                                     .' title="'.html_entities(t('info:never record')).'">'
                                     .t('Never Record').'</a>';
                    break;
                case 'record':
                    $commands[$key] = '<a href="'.root.'tv/upcoming?record=yes&'.$urlstr.'"'
                                     .' title="'.html_entities(t('info: record this')).'">'
                                     .t('Record This').'</a>';
                    break;
                case 'forget_old':
                    $commands[$key] = '<a href="'.root.'tv/upcoming?forget_old=yes&'.$urlstr.'"'
                                     .' title="'.html_entities(t('info:forget old')).'">'
                                     .t('Forget Old').'</a>';
                    break;
                case 'activate':
                    $commands[$key] = '<a href="'.root.'tv/upcoming?record=yes&'.$urlstr.'"'
                                     .' title="'.html_entities(t('info: activate recording')).'">'
                                     .t('Activate').'</a>';
                    break;
                case 'default':
                    $commands[$key] = '<a href="'.root.'tv/upcoming?default=yes&'.$urlstr.'"'
                             .' title="'.html_entities(t('info: default recording')).'">'
                             .t('Default').'</a>';
                    break;
            }
        }

    // A program id counter for popup info
        if (show_popup_info) {
            static $program_id_counter = 0;
            $program_id_counter++;
        }

    // Print a dividing row if grouping changes
        if ($group_field == "airdate")
            $cur_group = strftime($_SESSION['date_listing_jump'], $show->starttime);
        elseif ($group_field == "channum")
            $cur_group = $show->channel->name;
        elseif ($group_field == "title")
            $cur_group = $show->title;

        if ( $cur_group != $prev_group && $group_field != '' ) {
?><tr class="list_separator">
    <td colspan="8" class="list_separator"><?php echo $cur_group ?></td>
</tr><?php
        }

    // Print the content
?><tr class="<?php echo $css_class ?>">
<?php if (!empty($group_field)) echo "    <td class=\"list\">&nbsp;</td>\n" ?>
    <td class="-status rec_class <?php echo $rec_class ?>"><?php echo $rec_char ?></td>
    <td class="-title <?php echo $show->css_class ?>"><?php
    // Print the link to edit this scheduled recording
        echo '<a';
        if (show_popup_info)
            echo show_popup("program_$program_id_counter", $show->details_list(), NULL, 'popup');
        else
            echo ' title="',html_entities(strftime($_SESSION['time_format'], $show->starttime)
                         .' - '.strftime($_SESSION['time_format'], $show->endtime)
                         .' -- '
                         .$show->title
                         .($show->subtitle
                             ? ':  '.$show->subtitle
                             : '')), '"';
        echo ' href="', root, 'tv/detail/', $show->chanid, '/', $show->starttime, '">',
             $show->title,
             ($show->subtitle
                ? ':  '.$show->subtitle
                : ''),
             '</a>';
        ?></td>
    <td class="-channum"><?php echo $show->channel->channum, ' - ', $show->channel->name ?></td>
    <td class="-airdate"><?php echo strftime($_SESSION['date_scheduled'], $show->starttime) ?></td>
    <td class="-length"><?php  echo nice_length($show->length) ?></td>
<?php
        foreach ($commands as $command) {
            echo '    <td class="-commands commands">',$command,"</td>\n";
        }
?>
</tr><?php
        $prev_group = $cur_group;
        $row++;
    }
?>

</table>
<?php

// Print the page footer
    require 'modules/_shared/tmpl/'.tmpl.'/footer.php';

