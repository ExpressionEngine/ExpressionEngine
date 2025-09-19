<?php
/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */

namespace ExpressionEngine\Library\Date;

trait DateTrait
{
    public function addDatePickerScript()
    {
        $week_start = ee()->session->userdata('week_start', (ee()->config->item('week_start') ?: 'sunday'));
        ee()->javascript->set_global('date.week_start', $week_start);

        ee()->lang->loadfile('calendar');
        ee()->javascript->set_global('lang.date.today', lang('cal_today'));
        ee()->javascript->set_global('lang.date.months.full', array(
            lang('cal_january'),
            lang('cal_february'),
            lang('cal_march'),
            lang('cal_april'),
            lang('cal_may'),
            lang('cal_june'),
            lang('cal_july'),
            lang('cal_august'),
            lang('cal_september'),
            lang('cal_october'),
            lang('cal_november'),
            lang('cal_december')
        ));
        ee()->javascript->set_global('lang.date.months.abbreviated', array(
            lang('cal_jan'),
            lang('cal_feb'),
            lang('cal_mar'),
            lang('cal_apr'),
            lang('cal_may'),
            lang('cal_june'),
            lang('cal_july'),
            lang('cal_aug'),
            lang('cal_sep'),
            lang('cal_oct'),
            lang('cal_nov'),
            lang('cal_dec')
        ));
        ee()->javascript->set_global('lang.date.days', array(
            lang('cal_su'),
            lang('cal_mo'),
            lang('cal_tu'),
            lang('cal_we'),
            lang('cal_th'),
            lang('cal_fr'),
            lang('cal_sa'),
        ));
        ee()->cp->add_js_script(array(
            'file' => array('cp/date_picker'),
        ));
    }
}