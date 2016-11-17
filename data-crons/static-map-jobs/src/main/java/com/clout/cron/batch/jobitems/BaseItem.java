package com.clout.cron.batch.jobitems;

import java.io.Serializable;
import java.util.HashMap;
import java.util.Map;

import com.clout.cron.batch.metadata.TaskInfo;

public abstract class BaseItem implements Serializable {

	/**
	 * 
	 */
	private static final long serialVersionUID = 1L;
	
	protected Map<String, TaskInfo> metadata = new HashMap<String, TaskInfo>();
	protected Integer storeId;

	public Map<String, TaskInfo> getMetadata() {
		return metadata;
	}

	public void addMetadata(String className, TaskInfo taskMd) {
		metadata.put(className, taskMd);
	}
	
	public TaskInfo getTaskInfo(String className) {
		return metadata.get(className);
	}
	
	
}
