package com.clout.test.tasklet;

import java.text.ParseException;
import java.text.SimpleDateFormat;
import java.util.Calendar;
import java.util.Date;

import org.junit.Assert;
import org.junit.Test;

import com.clout.cron.batch.util.DateFormatUtil;

public class FormatDateTest {

	@Test
	public void test90Days() throws ParseException{		
		String date = "2016-05-23 23:11:32";
		SimpleDateFormat stringtoDate = new SimpleDateFormat("yyyy-MM-dd HH:mm:ss");
		Date testDate = stringtoDate.parse(date);
		
		SimpleDateFormat dayDate = new SimpleDateFormat("yyyyMMdd");
		String longString = dayDate.format(testDate);
		System.out.println(longString);
	}
	
	@Test
	public void testDateRange() {

		Long date1 = 20160101L;
		Long date2 = 20161201L;
		Long date3 = 20170101L;
		 
		Assert.assertTrue(date1 < date2);
		Assert.assertTrue(date3 > date2);
		Assert.assertTrue(date2 < date3);
		
		SimpleDateFormat dayDate = new SimpleDateFormat("yyyyMMdd");
		String today = dayDate.format(new Date());
		
		System.out.println(today);
		
		Calendar c = Calendar.getInstance();
		c.setTime(new Date());
		c.add(Calendar.DAY_OF_YEAR, -90);
		
		System.out.println("90 days ago " + dayDate.format(c.getTime()));
		
		System.out.println(DateFormatUtil.moveBy(-90, Calendar.DAY_OF_YEAR));
		
		Assert.assertTrue(Long.parseLong(DateFormatUtil.moveBy(0, Calendar.DAY_OF_YEAR)) > Long.parseLong(DateFormatUtil.moveBy(-90, Calendar.DAY_OF_YEAR)));
		Assert.assertTrue(Long.parseLong(DateFormatUtil.moveBy(0, Calendar.MONTH)) > Long.parseLong(DateFormatUtil.moveBy(-6, Calendar.MONTH)));
	}
}
