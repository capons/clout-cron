package com.clout.cron.batch.util;

import org.apache.commons.lang.StringEscapeUtils;
import org.apache.commons.lang.StringUtils;
import org.apache.log4j.Logger;

/**
 * Eventually centralize this logic for both address and store name since they do pretty similar things
 * @author Ung
 *
 */
public class StringSanitizerUtil {
	private final static Logger logger = Logger.getLogger(StringSanitizerUtil.class);
	
	public static String sanitize(String value) {
		logger.debug("'" + value + "'");
		value = StringEscapeUtils.unescapeHtml(value);
		value = value.replaceAll("(- CLOSED)", " ");
		value = value.replaceAll("[^\\dA-Za-z0-9\\s]", "");		
		value = value.replaceAll("[\\s]+", " ");
		if(value != null && !value.isEmpty()) {
			String newValue = value.replaceAll(",", "");
			logger.debug("'" + newValue + "'");
			return StringUtils.trimToEmpty(newValue);
		}
		return "";
	}
	
	public static String normalizeAndSanitize(String value) {
		logger.debug("'" + value + "'");
		value = StringEscapeUtils.unescapeHtml(value);
		value = value.replaceAll("(- CLOSED)", " ");
		value = value.replaceAll("[^\\dA-Za-z0-9\\s]", " ");		
		value = value.replaceAll("[\\s]+", " ");
		value = value.replaceFirst("[\\d]+", " ");
		if(value != null && !value.isEmpty()) {
			String[] split = value.split("[0-9]+");
			if(split.length > 1 || split[0] != null) {
				if(split[0].length() < 19) {
					value = split[0].substring(0, split[0].length());
				} else {
					value = split[0];
				}
			}
			
			if(value.length() > 19) {
				value = value.substring(0, 19);
			}
			String newValue = value.replaceAll(",", "");
			logger.debug("'" + newValue + "'");
			return StringUtils.trimToEmpty(newValue);
		}
		return "";
	}
}
