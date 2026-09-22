# ==============================================================================
# Script to export all 700 records from New Internship_be.accdb to MySQL format
# ==============================================================================
$ErrorActionPreference = "Stop"

$workspaceDir = "D:\Abubakar\AWT Internship Managment"
$dbPath = Join-Path $workspaceDir "New Internship_be.accdb"
$outSql = Join-Path $workspaceDir "database\migrate_access_data.sql"

Write-Host "Opening Access DB: $dbPath"
$connStr = "Driver={Microsoft Access Driver (*.mdb, *.accdb)};DBQ=$dbPath;"
$conn = New-Object System.Data.Odbc.OdbcConnection($connStr)
$conn.Open()

$cmd = $conn.CreateCommand()
$cmd.CommandText = "SELECT * FROM Student ORDER BY id ASC"
$adapter = New-Object System.Data.Odbc.OdbcDataAdapter($cmd)
$dt = New-Object System.Data.DataTable
[void]$adapter.Fill($dt)
$conn.Close()

Write-Host "Loaded $($dt.Rows.Count) rows from Access table 'Student'."

function Escape-Sql($val) {
    if ($val -eq [DBNull]::Value -or $null -eq $val) {
        return "NULL"
    }
    if ($val -is [bool]) {
        if ($val) { return "1" } else { return "0" }
    }
    if ($val -is [DateTime]) {
        return "'" + $val.ToString("yyyy-MM-dd HH:mm:ss") + "'"
    }
    if ($val -is [int] -or $val -is [long] -or $val -is [double] -or $val -is [decimal]) {
        return $val.ToString()
    }
    $s = $val.ToString().Trim()
    if ($s -eq "") {
        return "NULL"
    }
    $escaped = $s.Replace("\", "\\").Replace("'", "''")
    return "'$escaped'"
}

$columns = @(
    "id", "sname", "sterm", "sInstitute", "Hours", "dateassignfrom", "dateassignto",
    "dateprefferedfrom", "dateprefferedto", "DateofInternship", "Dateofentry", "Address",
    "Organizationname", "Mentor", "Designationanddepartment", "punctuality", "regularity",
    "productivity", "relationship_with_others", "Initiative", "Maturity", "Confidence",
    "Analytical_ability", "abilityhardword", "knowledge", "Assignedwork", "comments",
    "HRperson", "HRdesignation", "Request_Form", "Photograph", "CV", "Recommendation_Letter",
    "CNIC_copy", "Student_ID", "Email", "Degree", "Location", "iyear", "Date_of_Submission",
    "Cellnumber", "confirmed", "Waiting", "status", "GroupName", "GroupTime", "Assignment1",
    "Assignment2", "Assignment3", "Assignment4", "Assignment5", "Supervisor", "Department",
    "Father_name", "GroupN", "Summer", "Certificate_Period_Detail", "Remarks", "Assignments", "ERPNO"
)

$sb = New-Object System.Text.StringBuilder
[void]$sb.AppendLine("-- =======================================================")
[void]$sb.AppendLine("-- AWT Intern Management System (AWT-IMS)")
[void]$sb.AppendLine("-- Migrated Data from MS Access 'New Internship_be.accdb'")
[void]$sb.AppendLine("-- Total Records: $($dt.Rows.Count)")
[void]$sb.AppendLine("-- Generated on: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')")
[void]$sb.AppendLine("-- =======================================================")
[void]$sb.AppendLine("")
[void]$sb.AppendLine("SET FOREIGN_KEY_CHECKS = 0;")
[void]$sb.AppendLine("")

$batchSize = 25
$rowCount = 0

for ($i = 0; $i -lt $dt.Rows.Count; $i++) {
    $r = $dt.Rows[$i]
    $vals = @()
    
    $vals += Escape-Sql $r["id"]
    $vals += Escape-Sql $r["sname"]
    $vals += Escape-Sql $r["sterm"]
    $vals += Escape-Sql $r["sInstitute"]
    $vals += Escape-Sql $r["Hours"]
    $vals += Escape-Sql $r["dateassignfrom"]
    $vals += Escape-Sql $r["dateassignto"]
    $vals += Escape-Sql $r["dateprefferedfrom"]
    $vals += Escape-Sql $r["dateprefferedto"]
    $vals += Escape-Sql $r["DateofInternship"]
    $vals += Escape-Sql $r["Dateofentry"]
    $vals += Escape-Sql $r["Address"]
    $vals += Escape-Sql $r["Organizationname"]
    $vals += Escape-Sql $r["Mentor"]
    $vals += Escape-Sql $r["Designationanddepartment"]
    $vals += Escape-Sql $r["punctuality"]
    $vals += Escape-Sql $r["regularity"]
    $vals += Escape-Sql $r["productivity"]
    $vals += Escape-Sql $r["relationship with others"]
    $vals += Escape-Sql $r["Initiative"]
    $vals += Escape-Sql $r["Maturity"]
    $vals += Escape-Sql $r["Confidence"]
    $vals += Escape-Sql $r["Analytical ability"]
    $vals += Escape-Sql $r["abilityhardword"]
    $vals += Escape-Sql $r["knowledge"]
    $vals += Escape-Sql $r["Assignedwork"]
    $vals += Escape-Sql $r["comments"]
    $vals += Escape-Sql $r["HRperson"]
    $vals += Escape-Sql $r["HRdesignation"]
    $vals += Escape-Sql $r["Request Form"]
    $vals += Escape-Sql $r["Photograph"]
    $vals += Escape-Sql $r["CV"]
    $vals += Escape-Sql $r["Recommendation Letter"]
    $vals += Escape-Sql $r["CNIC copy"]
    $vals += Escape-Sql $r["Student ID"]
    $vals += Escape-Sql $r["Email"]
    $vals += Escape-Sql $r["Degree"]
    $vals += Escape-Sql $r["Location"]
    $vals += Escape-Sql $r["iyear"]
    $vals += Escape-Sql $r["Date of Submission"]
    $vals += Escape-Sql $r["Cellnumber"]
    
    $isConfirmed = [bool]$r["confirmed"]
    $isWaiting = [bool]$r["Waiting"]
    $vals += Escape-Sql $isConfirmed
    $vals += Escape-Sql $isWaiting
    
    # Compute status string
    $status = "pending"
    if ($isConfirmed) {
        $punc = 0
        if ($r["punctuality"] -ne [DBNull]::Value) { $punc = [int]$r["punctuality"] }
        if ($punc -gt 0) {
            $status = "completed"
        } else {
            $status = "confirmed"
        }
    } elseif ($isWaiting) {
        $status = "waiting"
    }
    $vals += "'$status'"

    $vals += Escape-Sql $r["GroupName"]
    $vals += Escape-Sql $r["GroupTime"]
    $vals += Escape-Sql $r["Assignment1"]
    $vals += Escape-Sql $r["Assignment2"]
    $vals += Escape-Sql $r["Assignment3"]
    $vals += Escape-Sql $r["Assignment4"]
    $vals += Escape-Sql $r["Assignment5"]
    $vals += Escape-Sql $r["Supervisor"]
    $vals += Escape-Sql $r["Department"]
    $vals += Escape-Sql $r["Father name"]
    $vals += Escape-Sql $r["GroupN"]
    $vals += Escape-Sql $r["Summer"]
    $vals += Escape-Sql $r["Certificate Period Detail"]
    $vals += Escape-Sql $r["Remarks"]
    $vals += Escape-Sql $r["Assignments"]
    $vals += Escape-Sql $r["ERPNO"]

    $line = "INSERT INTO `interns` (" + ($columns -join ", ") + ") VALUES (" + ($vals -join ", ") + ");"
    [void]$sb.AppendLine($line)
    $rowCount++
}

[void]$sb.AppendLine("")
[void]$sb.AppendLine("SET FOREIGN_KEY_CHECKS = 1;")
[void]$sb.AppendLine("-- Successfully exported $rowCount records.")

[System.IO.File]::WriteAllText($outSql, $sb.ToString(), [System.Text.Encoding]::UTF8)
Write-Host "Migration SQL generated successfully: $outSql (Total: $rowCount rows)"
