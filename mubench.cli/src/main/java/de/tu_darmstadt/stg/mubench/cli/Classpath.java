package de.tu_darmstadt.stg.mubench.cli;

public class Classpath {
    private String path;

    public Classpath(String path) {
        this.path = path;
    }

    public String[] getPaths() {
        return path.split(":");
    }

    public String getClasspath() {
        return path;
    }
}
