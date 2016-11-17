package com.clout.cron.batch.listeners;

import java.util.List;

import javax.annotation.Resource;

import org.apache.log4j.Logger;
import org.springframework.batch.core.ItemReadListener;
import org.springframework.batch.core.ItemWriteListener;
import org.springframework.context.annotation.Scope;
import org.springframework.stereotype.Component;

import com.clout.cron.batch.jobitems.JobState;
import com.clout.cron.batch.model.BaseModel;

/**
 * 
 * 
 * @author Ung
 *
 */
@Component("cronWriteListener")
@Scope("step")
public class CronWriteListener implements ItemWriteListener<BaseModel>, ItemReadListener<BaseModel> {
	private final static Logger logger = Logger.getLogger(CronWriteListener.class);
	
	@Resource
	private JobState jobState;

	
	@Override
	public void beforeWrite(List<? extends BaseModel> items) {
		logger.debug("ItemWriteListener - beforeWrite item count " + items.size());
	}

	
	
	@Override
	public void afterWrite(List<? extends BaseModel> items) {
		logger.debug("ItemWriteListener - afterWrite item count " + items.size());
	}

	
	
	@Override
	public void onWriteError(Exception exception, List<? extends BaseModel> items) {
		logger.debug("ItemWriteListener - onWriteError " + exception.getMessage());
		
		exception.printStackTrace();

		for(int i = 0; i < items.size(); i ++) {
			jobState.incrementFailed();
		}

		logger.error("Failed " + jobState.getFailed());
		logger.error("Success " + jobState.getSuccess());
	}



	@Override
	public void beforeRead() {
		logger.debug("ItemWriteListener - beforeRead");
	}



	@Override
	public void afterRead(BaseModel item) {
		logger.debug("ItemWriteListener - afterRead");
	}



	@Override
	public void onReadError(Exception ex) {
		ex.printStackTrace();
		logger.debug("ItemWriteListener - onReadError " + ex.getMessage());
	}

}
