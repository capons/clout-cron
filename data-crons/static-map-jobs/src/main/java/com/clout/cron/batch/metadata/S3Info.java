package com.clout.cron.batch.metadata;

import java.io.Serializable;

/**
 * Information for S3
 * 
 * Scalable - define instance per configuration
 * 
 * @author Ung
 *
 */
public class S3Info implements TaskInfo, Serializable {

	/**
	 * 
	 */
	private static final long serialVersionUID = 1L;

	/*
	 * Needed if you want to customize location of your authentication file
	 */
	private String profileName;

	/*
	 * optional authentication file required when using profileName
	 */
	private String certFilePath;

	private String bucketName;

	private String s3Host;

	public String getProfileName() {
		return profileName;
	}

	public void setProfileName(String profileName) {
		this.profileName = profileName;
	}

	public String getCertFilePath() {
		return certFilePath;
	}

	public void setCertFilePath(String certFilePath) {
		this.certFilePath = certFilePath;
	}

	public String getBucketName() {
		return bucketName;
	}

	public void setBucketName(String bucketName) {
		this.bucketName = bucketName;
	}

	public String getS3Host() {
		return s3Host;
	}

	public void setS3Host(String s3Host) {
		this.s3Host = s3Host;
	}

}
