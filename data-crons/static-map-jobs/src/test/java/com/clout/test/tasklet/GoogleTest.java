package com.clout.test.tasklet;

import java.io.UnsupportedEncodingException;
import java.util.HashMap;
import java.util.Map;

import javax.annotation.Resource;

import org.apache.log4j.Logger;
import org.junit.Test;
import org.junit.runner.RunWith;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.test.context.ContextConfiguration;
import org.springframework.test.context.junit4.SpringJUnit4ClassRunner;
import org.springframework.web.client.RestTemplate;

import com.clout.cron.batch.metadata.GoogleInfo;
import com.clout.cron.batch.metadata.GoogleInfo.GoogleMetadataBuilder;
import com.clout.cron.batch.task.GoogleGeocodeTask;
import com.clout.cron.batch.task.GooglePlacesTask;
import com.clout.cron.batch.task.GoogleServiceFactory;
import com.clout.cron.batch.task.ServiceNotFoundException;
import com.clout.cron.batch.task.TaskException;

@RunWith(SpringJUnit4ClassRunner.class)
@ContextConfiguration({"classpath:/spring/batch/jobs/common-context.xml",  "classpath:/spring/batch/database.xml", "classpath:/spring/batch/jobs/business-data.xml"})
public class GoogleTest {
	
	private final static Logger logger = Logger.getLogger(GoogleTest.class);
	
	@Value("${google.cron.access.key}")
	private String accessKey;
	
	@Value("${cron.job.google.places.url}")
	private String placesUrl;

	@Value("${cron.job.google.geocode.url}")
	private String geocodeUrl;
	
	@Resource
	private RestTemplate restTemplate;
	
	@Resource
	private JdbcTemplate jdbcTemplate;
	
	@Resource
	private GoogleServiceFactory googleServiceFactory;
	

	@Test
	public void testGeocodeParsing() throws UnsupportedEncodingException, TaskException, ServiceNotFoundException {
		//
		
		if(restTemplate == null) {
			logger.info("rest template is null");
		}
		
		Map<String, String> geocodePlaceholder = new HashMap<String, String>();
		geocodePlaceholder.put("\\{address\\}", "3631 Crenshaw Blvd Ste 101Los Angeles,CA 90016");
		
		logger.info("url " + geocodeUrl);
		
		GoogleInfo geocode = new GoogleMetadataBuilder().setAccessKey(accessKey).setGoogleServiceEndpoint(geocodeUrl).setPlaceholder(geocodePlaceholder).execute();

		logger.info(GoogleGeocodeTask.class.getSimpleName());
		
		/*
		 * Download image from google service
		 */
		googleServiceFactory.getTask(GoogleGeocodeTask.class.getSimpleName()).execute(geocode);
		
		
		
		Map<String, String> placesPlaceholder = new HashMap<String, String>();
		placesPlaceholder.put("\\{longitude\\}", geocode.getResults().get("longitude"));
		placesPlaceholder.put("\\{latitude\\}", geocode.getResults().get("latitude"));
		placesPlaceholder.put("\\{radius\\}", "50");
		
		
		GoogleInfo places = new GoogleMetadataBuilder().setAccessKey(accessKey).setGoogleServiceEndpoint(placesUrl).setPlaceholder(placesPlaceholder).execute();

		googleServiceFactory.getTask(GooglePlacesTask.class.getSimpleName()).execute(places);
	}
}
