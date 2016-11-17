package com.clout.cron.batch.task;

/**
 * Defines how service factories should be implemented  
 * 
 * @author Ung
 *
 */
public interface ServiceFactory {
	
	public Task getTask(String className) throws ServiceNotFoundException;

}
