package com.clout.cron.batch.listeners;

import javax.annotation.Resource;

import org.apache.log4j.Logger;
import org.springframework.batch.core.ExitStatus;
import org.springframework.batch.core.StepExecution;
import org.springframework.batch.core.StepExecutionListener;
import org.springframework.context.annotation.Scope;
import org.springframework.stereotype.Component;

import com.clout.cron.batch.jobitems.JobState;

/**
 * 
 * @author Ung
 *
 */
@Component("cronStepListener")
@Scope("step")
public class CronStepListener implements StepExecutionListener {
	private final static Logger logger = Logger.getLogger(CronStepListener.class);
	

	@Resource
	private JobState jobState;
	
	
	@Override
	public void beforeStep(StepExecution stepExecution) {
		logger.debug("StepExecutionListener - beforeStep");
	}

	@Override
	public ExitStatus afterStep(StepExecution stepExecution) {
		logger.debug("StepExecutionListener - afterStep success: " + jobState.getSuccess());
		return ExitStatus.COMPLETED;

	}

}
