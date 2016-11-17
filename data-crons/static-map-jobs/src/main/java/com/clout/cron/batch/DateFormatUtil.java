package com.clout.cron.batch;

import java.text.SimpleDateFormat;
import java.util.Calendar;
import java.util.Date;

import org.apache.log4j.Logger;

public class DateFormatUtil {
	private final static Logger logger = Logger.getLogger(DateFormatUtil.class);

	/*
	 * Calendar.DAY_OF_YEAR = 6
	 * Calendar.MONTH = 2
	 * 
	 */
	public static String moveBy(int unit, int calendarDateType) {
		SimpleDateFormat dayDate = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");

		Calendar c = Calendar.getInstance();
		c.setTime(new Date());
		c.add(calendarDateType, unit);
		
		String result = dayDate.format(c.getTime());
		logger.info("Move by calendarDateType: " + calendarDateType + " with frequency: " + unit + " result is " + result);
		return result;
	}

}
