package com.clout.cron.batch.metadata;

import java.io.Serializable;

/**
 * Image information
 * 
 * @author Ung
 *
 */
public class ImageInfo implements TaskInfo, Serializable {

	/**
	 * 
	 */
	private static final long serialVersionUID = 1L;

	private String imageName;
	private byte[] imageByte;
	private boolean exist;

	public String getImageName() {
		return imageName;
	}

	public void setImageName(String imageName) {
		this.imageName = imageName;
	}

	public byte[] getImageByte() {
		return imageByte;
	}

	public void setImageByte(byte[] imageByte) {
		this.imageByte = imageByte;
	}

	public boolean isExist() {
		return exist;
	}

	public void setExist(boolean exist) {
		this.exist = exist;
	}

}
