package com.clout.cron.batch.task;

import java.util.Iterator;

import javax.annotation.Resource;

import org.apache.log4j.Logger;
import org.jsoup.Jsoup;
import org.jsoup.nodes.Document;
import org.jsoup.nodes.Element;
import org.jsoup.select.Elements;
import org.springframework.web.client.RestTemplate;

import com.clout.cron.batch.metadata.TaskInfo;
import com.clout.cron.batch.metadata.YelpInfo;

public class YelpPagesTask implements Task {
	
	private final static Logger logger = Logger.getLogger(YelpPagesTask.class);

	@Resource
	private RestTemplate restTemplate;
	
	private int order;
	
	private Iterator<Element> businesses;
	
	private Element currentElement;
	
	private int totalBusinesses;
	
	private int currentPage = 0;
	
	@Override
	public void execute(TaskInfo... metadata) throws TaskException {
		YelpInfo info = (YelpInfo) metadata[0];
		
		try {
			logger.info("URL: " + info.getServiceEndpoint());
			Document doc = Jsoup.connect(info.getServiceEndpoint()).get();
			
			businesses = doc.select("div[class=biz-listing-large]").iterator(); // loses website
			
			if(businesses != null && businesses.hasNext()) {
				currentElement = businesses.next();
			}
			else {
				currentElement = null;
			}
			
			Elements totalPages = doc.select("span[class=pagination-results-window]");
			for(Element t : totalPages) {
				totalBusinesses = Integer.parseInt( t.text().split("of")[1].trim());
			}
			logger.info("totalBusinesses " + totalBusinesses);
		}catch (Exception e) {
			e.printStackTrace();
			logger.error("Error: " + e.getMessage());
		}
	}
	
	public boolean hasNext() {
		return currentElement == null ? false : true;
	}
	
	public void next() {
		if(businesses.hasNext()) {
			currentElement = businesses.next();
		}
		else {
			currentElement = null;
		}
	}
	
	public String getBusinessName() {
		try {
			Elements businessName = currentElement.select("span[class=indexed-biz-name]");
			Elements actualName = businessName.get(0).select("span[class=highlighted]");
			if(actualName != null && !actualName.isEmpty()) {
				for(Element b : actualName) {
					if(b.text() != null){
						return b.text();
					}
					return null;
				}
			}
		}catch(Exception e) {
			logger.error("Unable to retrieve business name");
		}
		return null;
	}
	
	public String getCategory() {
		StringBuilder builder = new StringBuilder();
		try {
			Elements cats = currentElement.select("span[class=category-str-list] a");

			if(cats != null && !cats.isEmpty()) {
				for(Element a : cats) {
					if(a.text() != null) {
						builder.append(a.text().trim() + ",");
					}
				}
			}
		}catch(Exception e) {
			logger.error("Unable to retrieve city");
		}
		return builder.toString().substring(0, builder.toString().length() - 1);
	}
	
	public String getNextFullAddress(){
		try {
			Elements addrs = currentElement.getElementsByTag("address");
		
			if(addrs != null && !addrs.isEmpty()) {
				for(Element a : addrs) {
					if(a.text() != null) {
						return a.text().trim();
					}
					return "";
				}
			}
		}catch(Exception e) {
			logger.error("Unable to retrieve city");
		}
		return "";
	}	
	
	public String getNextPhones(){
		try {
			Elements phones = currentElement.getElementsByClass("biz-phone");
		
			if(phones != null && !phones.isEmpty()) {
				for(Element p : phones) {
					if(p.text() != null) {
						return p.text().replaceAll("[(|)|\\s|-]+", "").trim();
					}
					return null;
				}
			}
		}catch(Exception e) {
			logger.error("Unable to retrieve phone number");
		}
		return null;
		
	}
	
	public int getTotalBusinesses() {
		return totalBusinesses;
	}

	public int getCurrentPage() {
		return currentPage;
	}

}
