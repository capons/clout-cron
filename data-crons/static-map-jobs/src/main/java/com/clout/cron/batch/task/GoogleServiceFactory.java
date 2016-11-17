package com.clout.cron.batch.task;

import java.util.HashMap;
import java.util.List;
import java.util.Map;

/**
 * Defines a list of tasks required per google API service
 * 
 * 
 * @author Ung
 *
 */
public class GoogleServiceFactory implements ServiceFactory {
	

	private Map<String, Task> tasks;
	
		
	public Task getTask(String className) throws ServiceNotFoundException {
		Task task =  tasks.get(className);
		if(task == null) {
			throw new ServiceNotFoundException("Unable to find service [" + className + "]");
		}
		return task;
	}
	
	
	public GoogleServiceFactory(Map<String, Task> tasks) {
		this.tasks = tasks;
	}
	
	
}
