package com.clout.cron.batch.util;

import java.util.UUID;

public class UUIDGenerator {
	public static String nextUUID() {
		return UUID.randomUUID().toString();
	}
}
