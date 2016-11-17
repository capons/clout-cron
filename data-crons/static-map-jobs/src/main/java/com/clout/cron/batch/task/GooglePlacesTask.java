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

public class GooglePlacesTask  implements Task {
	private final static Logger logger = Logger.getLogger(GooglePlacesTask.class);

	@Resource
	private RestTemplate restTemplate;
	
	private int order;

	/*
	 * Requires GoogleMetadata and ImageMetadata in that respective order
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
	
			HttpEntity<byte[]> response = restTemplate.exchange(googleMetadata.getServiceEndpoint(), HttpMethod.GET, request, byte[].class);
	
			byte[] resultString = response.getBody();
			
			logger.debug(new String(resultString));
			
			JSONObject obj = new JSONObject(new String(resultString));
			JSONArray array = obj.getJSONArray("results");
			
			for(int i = 0; i < array.length(); i ++) {
				JSONObject result = (JSONObject) array.get(i);
				
				JSONObject geometry = result.getJSONObject("geometry");
				JSONObject location = geometry.getJSONObject("location");
				
				googleMetadata.getResults().put("icon", result.getString("icon"));
				googleMetadata.getResults().put("name", result.getString("name"));
				googleMetadata.getResults().put("vicinity", result.getString("vicinity"));
				googleMetadata.getResults().put("latitude", location.getString("lat"));
				googleMetadata.getResults().put("longitude", location.getString("lng"));
				
				logger.info("result " + result.toString());

				
				logger.debug(googleMetadata.getResults().get("longitude") + " " + googleMetadata.getResults().get("latitude"));
			}
			
			logger.info("Places API successful " + googleMetadata.getServiceEndpoint());
		}
		catch(Exception e) {
			throw new TaskException("Unable to download file [" + googleMetadata.getServiceEndpoint() + "] due to " + e.getMessage());
		}
	}

}
