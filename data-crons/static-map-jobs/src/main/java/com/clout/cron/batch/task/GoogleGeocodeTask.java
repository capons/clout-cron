package com.clout.cron.batch.task;

import javax.annotation.Resource;

import org.apache.log4j.Logger;
import org.codehaus.jettison.json.JSONArray;
import org.codehaus.jettison.json.JSONObject;
import org.springframework.http.HttpEntity;
import org.springframework.http.HttpHeaders;
import org.springframework.http.HttpMethod;
import org.springframework.http.MediaType;
import org.springframework.web.client.RestTemplate;

import com.clout.cron.batch.metadata.GoogleInfo;
import com.clout.cron.batch.metadata.TaskInfo;

public class GoogleGeocodeTask  implements Task {
	private final static Logger logger = Logger.getLogger(GoogleGeocodeTask.class);

	@Resource
	private RestTemplate restTemplate;
	
	private int order;

	/*
	 * https://maps.googleapis.com/maps/api/geocode/json?address=1600+Amphitheatre+Parkway,+Mountain+View,+CA&key=YOUR_API_KEY
	 * 
	 * (non-Javadoc)
	 * @see com.dummy.batch.task.Task#execute(com.dummy.batch.metadata.TaskMetadata[])
	 */
	@Override
	public void execute(TaskInfo... metadata) throws TaskException {
		GoogleInfo googleMetadata = (GoogleInfo) metadata[0];

		try {
			HttpHeaders headers = new HttpHeaders();
			headers.setContentType(MediaType.TEXT_PLAIN);
	
			HttpEntity<String> request = new HttpEntity<String>(headers);
			
			logger.debug("service endpoint " + googleMetadata.getServiceEndpoint());
			
			HttpEntity<byte[]> response = restTemplate.exchange(googleMetadata.getServiceEndpoint(), HttpMethod.GET, request, byte[].class);
	
			byte[] resultString = response.getBody();
	
			logger.debug(new String(resultString));
			
			JSONObject obj = new JSONObject(new String(resultString));
			JSONArray array = obj.getJSONArray("results");
			
			logger.debug("ARRAY: " + array.toString());
			
			for(int i = 0; i < array.length(); i ++) {
				logger.debug("i is " + i);
				
				JSONObject result = (JSONObject) array.get(i);
				
				logger.debug("OBJECT: " + result.toString());
				
				JSONObject geometry = result.getJSONObject("geometry");
				JSONObject location = geometry.getJSONObject("location");
				String longitude = location.getString("lng");
				String latitude = location.getString("lat");
				
				googleMetadata.getResults().put("longitude", location.getString("lng"));
				googleMetadata.getResults().put("latitude", location.getString("lat"));
				
				logger.debug(longitude + " " + latitude);
			}

			logger.info("Geocode successful " + googleMetadata.getServiceEndpoint());
		}
		catch(Exception e) {
			e.printStackTrace();
			throw new TaskException("Unable to download file [" + googleMetadata.getServiceEndpoint() + "] due to " + e.getMessage());
		}
	}

}
