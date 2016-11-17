package com.clout.cron.batch.task;

import java.util.Iterator;

import javax.annotation.Resource;

import org.apache.commons.lang.StringUtils;
import org.apache.log4j.Logger;
import org.jsoup.Jsoup;
import org.jsoup.nodes.Document;
import org.jsoup.nodes.Element;
import org.jsoup.select.Elements;
import org.springframework.web.client.RestTemplate;

import com.clout.cron.batch.metadata.TaskInfo;
import com.clout.cron.batch.metadata.YellowPageInfo;

public class YellowPagesTask implements Task {
	
	private final static Logger logger = Logger.getLogger(YellowPagesTask.class);

	@Resource
	private RestTemplate restTemplate;
	
	private int order;
	
	private Iterator<Element> businesses;
	
	private Element currentElement;
	
	private int totalBusinesses;
	
	private int currentPage;
	
	@Override
	public void execute(TaskInfo... metadata) throws TaskException {
		YellowPageInfo info = (YellowPageInfo) metadata[0];
		
		try {
			logger.info("URL: " + info.getServiceEndpoint());
			Document doc = Jsoup.connect(info.getServiceEndpoint()).get();
			
			/*
			 * Not sure what it is but this logic resulted in no stores.  Is it an environment or is it something else
			try {
				businesses = doc.select("article[class=business-card]").iterator();
			}catch(Exception e) {
				logger.info("Not able to parse for article[class=business-card]");
			}
			
			if(businesses == null) {
				businesses = doc.select("div[class=info]").iterator(); // loses website
			}
			*/
			
			businesses = doc.select("div[class=info]").iterator(); // loses website
			
			if(businesses != null && businesses.hasNext()) {
				currentElement = businesses.next();
			}
			else {
				currentElement = null;
			}
			
			Elements page = doc.select("span[class=disabled]");
			for(Element pa : page) {
				currentPage = Integer.parseInt(pa.text());
			}
			
			Elements totalPages = doc.select("div[class=pagination] p");
			for(Element t : totalPages) {
				totalBusinesses = Integer.parseInt( StringUtils.trim( t.text().substring( t.text().indexOf("of ") + 2, t.text().indexOf("results") ) ) );
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
			Elements businessName = currentElement.getElementsByClass("business-name");
		
			if(businessName != null && !businessName.isEmpty()) {
				for(Element b : businessName) {
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
	
	public String getNextCity(){
		try {
			Elements addrs = currentElement.getElementsByClass("locality");
		
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
	
	public String getNextStreetAddress(){
		try {
			Elements addrs = currentElement.getElementsByClass("street-address");
		
			if(addrs != null && !addrs.isEmpty()) {
				for(Element a : addrs) {
					if(a.text() != null) {
						return a.text().trim();
					}
					return "";
				}
			}
		}catch(Exception e) {
			logger.error("Unable to retrieve street address");
		}
		return "";
	}	
	
	public String getNextPostalCode(){
		try {
			Elements addrs = currentElement.getElementsByClass("postalCode");
		
			if(addrs != null && !addrs.isEmpty()) {
				for(Element a : addrs) {
					if(a.text() != null) {
						return a.text().trim();
					}
					return "";
				}
			}
		}catch(Exception e) {
			logger.error("Unable to retrieve postal code");
		}
		return "";
	}	
	
	public String getNextAddress(){
		try {
			Elements addrs = currentElement.getElementsByClass("adr");
		
			if(addrs != null && !addrs.isEmpty()) {
				for(Element a : addrs) {
					if(a.text() != null) {
						return StringUtils.remove(StringUtils.remove(a.text(),"Serving the"), "area.");
					}
					return "";
				}
			}
		}catch(Exception e) {
			logger.error("Unable to retrieve full address");
		}
		return "";
	}
	
	public String getNextPhones(){
		try {
			Elements phones = currentElement.getElementsByClass("primary");
		
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
	
	
	public String getNextWebsite() {
		try {
			Elements website = currentElement.getElementsByClass("track-visit-website");
			if(website != null && !website.isEmpty()) {
				for(Element p : website) {
					if(p.attr("href") != null) {
						return p.attr("href").substring(0,  p.attr("href").indexOf("cid"));
					}
					return null;
				}
			}
		}catch(Exception e) {
			try {
				Elements website = currentElement.parent().getElementsByClass("track-visit-website");
				if(website != null && !website.isEmpty()) {
					for(Element p : website) {
						if(p.attr("href") != null) {
							return p.attr("href").substring(0,  p.attr("href").indexOf("cid"));
						}
						return null;
					}
				}
			}catch(Exception f) {
				logger.error("Unable to retrieve website");
			}
			logger.error("Unable to retrieve website");
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
