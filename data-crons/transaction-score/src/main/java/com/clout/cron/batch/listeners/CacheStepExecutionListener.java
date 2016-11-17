package com.clout.cron.batch.listeners;

import org.springframework.batch.core.ExitStatus;
import org.springframework.batch.core.StepExecution;
import org.springframework.batch.core.StepExecutionListener;
import org.springframework.beans.factory.annotation.Value;
import org.springframework.context.annotation.Scope;
import org.springframework.stereotype.Component;

@Component("cacheStepExecutionListener")
@Scope("step")
public class CacheStepExecutionListener implements StepExecutionListener{

	/*
	 * store ids passed into the job to be cached
	 */
	@Value("#{jobParameters['storeIds']}")
	private String storeIds;
	
	@Override
	public void beforeStep(StepExecution stepExecution) {
		stepExecution.getJobExecution().getExecutionContext().put("failedStoreIds", storeIds);
	}

	@Override
	public ExitStatus afterStep(StepExecution stepExecution) {
		return ExitStatus.COMPLETED;
	}



}
