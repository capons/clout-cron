package com.clout.cron.batch.metadata;

import java.io.UnsupportedEncodingException;
import java.util.HashMap;
import java.util.Map;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import com.clout.cron.batch.model.Naics;

public class YellowPageInfo implements TaskInfo {

	private String host;
	
	private Naics naics;
	
	public YellowPageInfo(String serviceEndpoint, Naics naics) {
		this.host = serviceEndpoint;
		this.naics = naics;
	}

	/*
	 * Requires GoogleMetadataBuilder to generate the correct download URL
	 */
	public String getServiceEndpoint(){
		return this.host;
	}
	
	public Naics getNaics() {
		return this.naics;
	}
	
	public static class YellowPageMetadataBuilder {
		private final static Logger logger = LoggerFactory.getLogger(YellowPageMetadataBuilder.class);
		private String serviceEndpoint = "http://www.yellowpages.com/search?search_terms={category}&geo_location_terms={location}&page={page}";
		private Map<String, String> placeholder = new HashMap<String, String>();
		private Naics naics;

		public YellowPageMetadataBuilder setServiceEndpoint(String serviceEndpoint) {
			this.serviceEndpoint = serviceEndpoint;
			return this;
		}

		public YellowPageMetadataBuilder setCategory(String category) throws UnsupportedEncodingException {
			logger.info("Setting Category " + category);
			this.placeholder.put("\\{category\\}", category);
			return this;
		}
		
		public YellowPageMetadataBuilder setLocation(String location) throws UnsupportedEncodingException {
			this.placeholder.put("\\{location\\}", location);
			return this;
		}
		
		public YellowPageMetadataBuilder setPage(String page) {
			this.placeholder.put("\\{page\\}", page);
			return this;
		}
		
		public YellowPageMetadataBuilder setNaics(Naics naics) {
			this.naics = naics;
			return this;
		}

		/*
		 * Build the metadata class
		 */
		public YellowPageInfo execute() {
			for(Map.Entry<String, String> entry : placeholder.entrySet()) {
				this.serviceEndpoint = this.serviceEndpoint.replaceAll(entry.getKey(), entry.getValue());
			}
			
			return new YellowPageInfo(this.serviceEndpoint, this.naics);
		}

	}

}
