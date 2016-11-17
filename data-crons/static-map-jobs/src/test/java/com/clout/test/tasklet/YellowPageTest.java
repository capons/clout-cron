package com.clout.test.tasklet;

import java.io.UnsupportedEncodingException;
import java.util.regex.Matcher;
import java.util.regex.Pattern;

import org.jsoup.Jsoup;
import org.jsoup.nodes.Document;
import org.jsoup.nodes.Element;
import org.jsoup.select.Elements;
import org.junit.Test;

import com.clout.cron.batch.metadata.YellowPageInfo;
import com.clout.cron.batch.metadata.YellowPageInfo.YellowPageMetadataBuilder;

public class YellowPageTest {
	String[] filters = new String[] {"Serving the"};
	

	@Test
	public void testYw() throws UnsupportedEncodingException {
		YellowPageInfo info = new YellowPageMetadataBuilder().setCategory("insurance").setLocation("los angeles, ca").setPage("1").execute();
		
		try {
			Document doc = Jsoup.connect(info.getServiceEndpoint()).get();
			
			Elements businesses = doc.select("div[class=info]");
			
			int count = 0;
			for(Element e : businesses) {
				count ++;
				Elements businessName = e.getElementsByClass("business-name");
				for(Element b : businessName) {
					System.out.println(b.text());
				}
				
				Elements addrs = e.getElementsByClass("adr");
				for(Element a : addrs) {
					System.out.println(a.text());
					String fullAddress = a.text();
					Pattern zipcode = Pattern.compile("[0-9]{5}");
					// find all links in page
					Matcher page = zipcode.matcher(fullAddress);
					while(page.find()){
						String zippart = page.group(0);
						System.out.println("zip " + zippart);
					}
					
					String[] parts = fullAddress.split(",");
					
					if(parts.length == 3) {
						System.out.println(parts[0]);
						System.out.println(parts[1]);
						System.out.println(parts[2]);
					}
					
					String[] pices = fullAddress.toUpperCase().split("Los Angeles,CA".toUpperCase());
					
					if(pices != null) {
						System.out.println(pices[0]);
						//System.out.println(pices[1]);
					}
					
				}
				
				Elements phones = e.getElementsByClass("primary");
				for(Element p : phones) {
					System.out.println(p.text());
				}
				
				// a class="track-visit-website"
				//href="http://capphysicians.com"
				
				Elements website = e.getElementsByClass("track-visit-website");
				for(Element p : website) {
					System.out.println(p.attr("href").substring(0,  p.attr("href").indexOf("cid")));
				}
				
				System.out.println("next");
			}
			
			Elements page = doc.select("span[class=disabled]");
			for(Element pa : page) {
				System.out.println(pa.text());
			}
			
			System.out.println("Business Per Page " + count);
			
			Elements totalPages = doc.select("div[class=pagination] p");
			for(Element t : totalPages) {
				System.out.println(t.text().substring(t.text().indexOf("of ") + 2, t.text().indexOf("results")));
			}
			
			/*
			Elements businessNames = doc.select("a[class=business-name]"); // a with href
			Elements addresses = doc.select("p[class=adr]");
			Elements phones = doc.select("li[class=phone primary]");
			Elements page = doc.select("span[class=disabled]");
			*/
		}catch (Exception e) {
			
		}
	}
}
