package com.clout.cron.batch.staticmap;

import java.util.List;

import javax.annotation.Resource;

import org.apache.log4j.Logger;
import org.springframework.batch.item.ItemWriter;
import org.springframework.context.annotation.Scope;
import org.springframework.stereotype.Component;

import com.clout.cron.batch.CronJobException;
import com.clout.cron.batch.jobitems.BaseItem;
import com.clout.cron.batch.jobitems.JobState;
import com.clout.cron.batch.jobitems.StaticMapItem;
import com.clout.cron.batch.metadata.GoogleInfo;
import com.clout.cron.batch.metadata.ImageInfo;
import com.clout.cron.batch.metadata.S3Info;
import com.clout.cron.batch.task.GoogleDownloadTask;
import com.clout.cron.batch.task.GoogleServiceFactory;
import com.clout.cron.batch.task.S3ResourceExistTask;
import com.clout.cron.batch.task.S3UploadTask;

/**
 * Writer class for google static map.  Handles uploading a static map to 
 * S3 if it does not exist.
 * 
 * @author Ung
 *
 */
@Component("staticMapWriter")
@Scope("step")
public class StaticMapWriter implements ItemWriter<BaseItem> {
	
	private final static Logger logger = Logger.getLogger(StaticMapWriter.class);
	
	@Resource
	private JobState jobState;
	
	@Resource
	private S3Info s3Info;
	
	@Resource
	private GoogleServiceFactory googleServiceFactory;

	@Override
	public void write(List<? extends BaseItem> items) throws Exception {
		logger.debug("In writer with " + items.size());
		
		for(BaseItem item : items) {
			try {
				StaticMapItem staticMapItem = (StaticMapItem) item;
	
				ImageInfo imageMetadata = (ImageInfo) staticMapItem.getTaskInfo(ImageInfo.class.getSimpleName());
				GoogleInfo googleMetadata = (GoogleInfo) staticMapItem.getTaskInfo(GoogleInfo.class.getSimpleName());
	
	
				/*
				 * check if resource exist
				 */
				googleServiceFactory.getTask(S3ResourceExistTask.class.getSimpleName()).execute(s3Info, imageMetadata);
				
	
				/*
				 * image does not exist in s3
				 */
				if(imageMetadata.isExist() == false) {
					logger.debug("Image does not exist");
					/*
					 * Download image from google service
					 */
					googleServiceFactory.getTask(GoogleDownloadTask.class.getSimpleName()).execute(googleMetadata, imageMetadata);
					/*
					 * upload the image
					 */
					googleServiceFactory.getTask(S3UploadTask.class.getSimpleName()).execute(s3Info, imageMetadata);
					jobState.incrementSuccess();
					logger.debug("Total success " + jobState.getSuccess());					
				}
				else {
					logger.debug("Do nothing Image already exist in s3 server for " + staticMapItem.getStoreId());
					jobState.incrementAlreadyExist();
				}
				
				jobState.setLastProcessedKey(staticMapItem.getStoreId());
			}catch(Exception e) {
				jobState.incrementFailed();
				logger.error("Write Error: Failed " + jobState.getFailed() + " on " + jobState.getLastProcessedKey() + " due to " + e.getMessage());
			}
		}
		logger.info("Success: " + jobState.getSuccess() + " Failed: " + jobState.getFailed() + " Exist " + jobState.getAlreadyExist() + " for key " + jobState.getLastProcessedKey());
	}

}
