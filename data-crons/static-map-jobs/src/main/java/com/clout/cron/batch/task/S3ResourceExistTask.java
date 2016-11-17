package com.clout.cron.batch.task;

import java.io.IOException;

import org.apache.log4j.Logger;

import com.amazonaws.AmazonServiceException;
import com.amazonaws.services.s3.AmazonS3;
import com.amazonaws.services.s3.model.S3Object;
import com.clout.cron.batch.metadata.ImageInfo;
import com.clout.cron.batch.metadata.S3Info;
import com.clout.cron.batch.metadata.TaskInfo;

/**
 * Checks if a given resource exist in amazon s3 server
 * 
 * @author Ung
 *
 */
public class S3ResourceExistTask implements Task {
	private final static Logger logger = Logger.getLogger(S3ResourceExistTask.class);
	
	private int order;
	
	private AmazonS3 s3Client;
	
	/*
	 * Requires S3Metadata and ImageMetadata in that respective order
	 * 
	 * (non-Javadoc)
	 * @see com.dummy.batch.task.Task#execute(com.dummy.batch.metadata.TaskMetadata[])
	 */
	@Override
	public void execute(TaskInfo... metadata) throws TaskException {
		S3Info s3metadata = (S3Info) metadata[0];
		ImageInfo imageMetadata = (ImageInfo) metadata[1];
		
		String fileName = imageMetadata.getImageName();
		S3Object object = null;
		try {
			//AmazonS3 s3 = new AmazonS3Client(new ProfileCredentialsProvider());
			//s3.createBucket(s3metadata.getBucketName());
			object = s3Client.getObject(s3metadata.getBucketName(), fileName);
			object.close();
			imageMetadata.setExist(true);
			logger.debug("S3 exist " + fileName);
		} catch (AmazonServiceException e) {
			logger.debug("S3 does not exist " + fileName);
			String errorCode = e.getErrorCode();
			if (!errorCode.equals("NoSuchKey")) {
				throw new TaskException("Unable to check server for file [" + fileName + "] due to " + e.getMessage());
			}
			imageMetadata.setExist(false);
		}
		catch (Exception e) {
			throw new TaskException("Unable to check server for file [" + fileName + "] due to " + e.getMessage());
		}finally {
			if(object != null) {
				try {
					object.close();
				} catch (IOException e) {
					logger.error("Unable to close s3Object connection");
				}
			}
		}
	}

	public void setS3Client(AmazonS3 s3Client) {
		this.s3Client = s3Client;
	}

}
