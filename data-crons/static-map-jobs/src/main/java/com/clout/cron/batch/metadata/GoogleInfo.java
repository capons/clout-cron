package com.clout.cron.batch.metadata;

import java.io.Serializable;
import java.util.HashMap;
import java.util.Map;

/**
 * Scalable - Define one instance per service endpoint configuration
 * 
 * @author Ung
 *
 */
public class GoogleInfo implements TaskInfo, Serializable {

	/**
	 * 
	 */
	private static final long serialVersionUID = 1L;

	private String serviceEndpoint;
	
	private Map<String, String> results = new HashMap<String, String>();
	
	public GoogleInfo(String url) {
		this.serviceEndpoint = url;
	}
	
	

	public Map<String, String> getResults() {
		return results;
	}


	/*
	 * Requires GoogleMetadataBuilder to generate the correct download URL
	 */
	public String getServiceEndpoint(){
		return this.serviceEndpoint;
	}
	
	public static class GoogleMetadataBuilder {
		private String googleServiceEndpoint;
		private Map<String, String> placeholder = new HashMap<String, String>();
		private String accessKey;
		
		public GoogleMetadataBuilder setGoogleServiceEndpoint(String googleServiceEndpoint) {
			this.googleServiceEndpoint = googleServiceEndpoint;
			return this;
		}

		public GoogleMetadataBuilder setPlaceholder(Map<String, String> placeholder) {
			this.placeholder = placeholder;
			return this;
		}

		public GoogleMetadataBuilder setAccessKey(String accessKey) {
			this.accessKey = accessKey;
			return this;
		}
		
		/*
		 * Build the metadata class
		 */
		public GoogleInfo execute() {
			for(Map.Entry<String, String> entry : placeholder.entrySet()) {
				this.googleServiceEndpoint = this.googleServiceEndpoint.replaceAll(entry.getKey(), entry.getValue());
			}
			this.googleServiceEndpoint += "&key=" + this.accessKey;
			
			return new GoogleInfo(this.googleServiceEndpoint);
		}
		
		
	}

}
