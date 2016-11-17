package com.clout.cron.batch;

public interface BatchJob {

	public void execute() throws CronJobException;
	
}
