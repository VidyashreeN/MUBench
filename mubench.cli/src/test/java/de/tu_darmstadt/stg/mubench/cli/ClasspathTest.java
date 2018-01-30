package de.tu_darmstadt.stg.mubench.cli;

import static org.junit.Assert.assertArrayEquals;
import static org.junit.Assert.assertEquals;

import org.junit.Test;

public class ClasspathTest {
    @Test
    public void getClasspath(){
        Classpath uut = new Classpath("-path-");
        assertEquals("-path-", uut.getClasspath());
    }

    @Test
    public void getPathsSplitsPath() {
        Classpath uut = new Classpath("-path-:-other-path-");
        assertArrayEquals(new String[]{"-path-", "-other-path-"}, uut.getPaths());
    }
}
