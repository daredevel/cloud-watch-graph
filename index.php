<?php
/**
 * Cloud Watch Graph
 * @author Valerio Galano <v.galano@daredevel.com>
 * @version 0.1
 */

?>
<form action="run.php" method="get">

    <label for="startTime">Date range</label><br/>
    <input name="startTime" id="startTime" value="2015-07-02T22:00:00Z">

    <label for="endTime"></label>
    <input name="endTime" id="endTime" value="2015-07-02T23:00:00Z"><br/>

    <br/>

    <label for="instanceId">InstanceId</label><br/>
    <input name="instanceId" id="instanceId"><br/>

    <br/>

    <label for="metricName">Metric</label><br/>
    <select id="metricName" name="metricName">
        <option value="CPUUtilization">CPUUtilization</option>
        <option value="DiskReadBytes">DiskReadBytes</option>
        <option value="DiskReadOps">DiskReadOps</option>
        <option value="DiskWriteBytes">DiskWriteBytes</option>
        <option value="DiskWriteOps">DiskWriteOps</option>
        <option value="NetworkIn">NetworkIn</option>
        <option value="DiskWriteOps">DiskWriteOps</option>
    </select><br/>

    <br/>

    <label for="downlaod">Download image</label><br/>
    <input id="downlaod" type="checkbox" name="download"/><br/>

    <input type="submit">
</form>
