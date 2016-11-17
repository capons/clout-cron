package com.clout.test.tasklet;

import java.io.UnsupportedEncodingException;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import org.jsoup.Jsoup;
import org.jsoup.nodes.Document;
import org.jsoup.nodes.Element;
import org.jsoup.select.Elements;
import org.junit.Test;

import com.clout.cron.batch.metadata.YelpInfo;
import com.clout.cron.batch.metadata.YelpInfo.YelpBuilder;
import com.clout.cron.batch.task.TaskException;
import com.clout.cron.batch.task.YelpPagesTask;

public class YelpTest {

	@Test
	public void testPageCount() {
		String page = "Showing 1-10 of 25";
		String[] tmp = page.split("of");
		System.out.println(tmp[1].trim());
	}
	
	@Test
	public void testYelp() throws UnsupportedEncodingException, TaskException {
		YelpInfo info = new YelpBuilder().setCategory("target").setLocation("los angeles, ca").setPage("0").execute();

		YelpPagesTask task = new YelpPagesTask();
		task.execute(info);
		
		while(task.hasNext()) {
			System.out.println(task.getBusinessName());
			System.out.println(task.getCategory());
			System.out.println(task.getCurrentPage());
			if(task.getNextFullAddress() != null && task.getNextFullAddress().contains("<br>")) {
				System.out.println(task.getNextFullAddress().split("<br>")[0] + " " + task.getNextFullAddress().split("<br>")[1]);
			}
			System.out.println(task.getNextPhones());
			task.next();
		}
	}

}
