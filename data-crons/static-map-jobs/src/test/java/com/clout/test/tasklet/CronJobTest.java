package com.clout.test.tasklet;

import java.io.ByteArrayInputStream;
import java.util.ArrayList;
import java.util.List;

import javax.annotation.Resource;

import org.apache.log4j.Logger;
import org.junit.Ignore;
import org.junit.Test;
import org.junit.runner.RunWith;
import org.springframework.http.HttpEntity;
import org.springframework.http.HttpHeaders;
import org.springframework.http.HttpMethod;
import org.springframework.http.MediaType;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.test.context.ContextConfiguration;
import org.springframework.test.context.junit4.SpringJUnit4ClassRunner;
import org.springframework.web.client.RestTemplate;

import com.amazonaws.AmazonClientException;
import com.amazonaws.AmazonServiceException;
import com.amazonaws.auth.profile.ProfileCredentialsProvider;
import com.amazonaws.services.s3.AmazonS3;
import com.amazonaws.services.s3.AmazonS3Client;
import com.amazonaws.services.s3.model.DeleteObjectsRequest;
import com.amazonaws.services.s3.model.DeleteObjectsRequest.KeyVersion;
import com.amazonaws.services.s3.model.DeleteObjectsResult;
import com.amazonaws.services.s3.model.MultiObjectDeleteException;
import com.amazonaws.services.s3.model.ObjectMetadata;
import com.amazonaws.services.s3.model.S3Object;
import com.amazonaws.services.s3.transfer.TransferManager;
import com.amazonaws.services.s3.transfer.Upload;
import com.clout.cron.batch.MainJob;

//@Ignore
@RunWith(SpringJUnit4ClassRunner.class)
@ContextConfiguration("classpath:/test-context.xml")
public class CronJobTest {
	
	private final static Logger logger = Logger.getLogger(CronJobTest.class);
	
	@Resource
	private RestTemplate restTemplate;
	
	@Resource
	private JdbcTemplate jdbcTemplate;

	//@Ignore
	@Test
	public void testSitemapJob() {
		MainJob job = new MainJob();
		job.main(new String[]{ "businessDataJob", "spring/batch/jobs/business-data.xml", "--city=los angeles", "--state=ca"});
	}
	
	@Ignore
	public void testDeleteCorruptData() {
		AmazonS3 s3 = new AmazonS3Client(new ProfileCredentialsProvider());		
		DeleteObjectsRequest multiObjectDeleteRequest = new DeleteObjectsRequest("pro-dw-s3b1");

		List<KeyVersion> keys = new ArrayList<KeyVersion>();
		keys.add(new KeyVersion("banner_138018.png"));



		multiObjectDeleteRequest.setKeys(keys);

		try {
		    DeleteObjectsResult delObjRes = s3.deleteObjects(multiObjectDeleteRequest);
		    System.out.format("Successfully deleted all the %s items.\n", delObjRes.getDeletedObjects().size());
		    			
		} catch (MultiObjectDeleteException e) {
		    e.printStackTrace();
		}
	}
	
	@Ignore
	public void testCloutDb() {
		Integer maxId = jdbcTemplate.queryForObject("select max(id) from stores", Integer.class);
		System.out.println("maxId " + maxId);
	}
	
	@Ignore
	public void testS3() {
		
		HttpHeaders headers = new HttpHeaders();
		headers.setContentType(MediaType.TEXT_PLAIN);

		HttpEntity<String> request = new HttpEntity<String>(headers);

		HttpEntity<byte[]> response = restTemplate.exchange("http://maps.googleapis.com/maps/api/staticmap?center=-95.71416,-95.71416&zoom=15&size=400x125&markers=icon:https://www.clout.com/assets/images/map_marker.png|-95.71416,-95.71416&key=AIzaSyCDGnJODSH0HteSJK7a3qDnrkPZImLY4BQ", HttpMethod.GET, request, byte[].class);

		byte[] resultString = response.getBody();
		
		String fileName = "banner_150000.png";
		
        try {
            TransferManager tm = new TransferManager(new ProfileCredentialsProvider());        
    		
            ObjectMetadata objectMetadata = new ObjectMetadata();
            Long contentLength = Long.valueOf(resultString.length);
            objectMetadata.setContentLength(contentLength);
            
            
            Upload upload = tm.upload("pro-dw-s3b1", fileName, new ByteArrayInputStream(resultString), objectMetadata);

        	// Or you can block and wait for the upload to finish
        	upload.waitForCompletion();
        	System.out.println("Upload complete.");
        } catch (AmazonClientException | InterruptedException e) {
        	e.printStackTrace();
        }

	}
	
	@Ignore
	public void testResource() {
		try {
			AmazonS3 s3 = new AmazonS3Client(new ProfileCredentialsProvider());
			S3Object object = s3.getObject("pro-dw-s3b1", "banner_15000.png");

			logger.debug("S3 exist ");
		} catch (AmazonServiceException e) {
			logger.debug("S3 does not exist ");
			e.printStackTrace();
			String errorCode = e.getErrorCode();
			if (!errorCode.equals("NoSuchKey")) {
				throw e;
			}
		}

	}
	
}
