package com.clout.cron.batch.task;

import java.io.ByteArrayInputStream;

import org.apache.log4j.Logger;

import com.amazonaws.AmazonClientException;
import com.amazonaws.auth.profile.ProfileCredentialsProvider;
import com.amazonaws.services.s3.AmazonS3;
import com.amazonaws.services.s3.model.ObjectMetadata;
import com.amazonaws.services.s3.transfer.TransferManager;
import com.amazonaws.services.s3.transfer.Upload;
import com.clout.cron.batch.metadata.ImageInfo;
import com.clout.cron.batch.metadata.S3Info;
import com.clout.cron.batch.metadata.TaskInfo;

/**
 * Uplaod image to amazon s3
 * 
 * @author Ung
 *
 */
public class S3UploadTask implements Task {
	private final static Logger logger = Logger.getLogger(S3UploadTask.class);

	private TransferManager transferManager;

	private int order;
	
	@Override
	public void execute(TaskInfo... metadata) throws TaskException {
		S3Info s3metadata = (S3Info) metadata[0];
		ImageInfo imageMetadata = (ImageInfo) metadata[1];
		
		String fileName = imageMetadata.getImageName();
		
        try {
            //TransferManager tm = new TransferManager(new ProfileCredentialsProvider());        
    		
            ObjectMetadata objectMetadata = new ObjectMetadata();
            Long contentLength = Long.valueOf(imageMetadata.getImageByte().length);
            objectMetadata.setContentLength(contentLength);
            
            
            Upload upload = transferManager.upload(s3metadata.getBucketName(), fileName, new ByteArrayInputStream(imageMetadata.getImageByte()), objectMetadata);

        	// Or you can block and wait for the upload to finish
        	upload.waitForCompletion();
        	logger.debug("Upload complete for " + fileName);
        } catch (AmazonClientException | InterruptedException e) {
        	logger.error("Unable to upload file, upload was aborted. due to " + e.getMessage());
        	throw new TaskException("Unable to upload file [" + fileName + "] due to " + e.getMessage());
        }
	}

	public void setTransferManager(TransferManager transferManager) {
		this.transferManager = transferManager;
	}

}
