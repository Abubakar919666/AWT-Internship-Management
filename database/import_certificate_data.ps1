# ==============================================================================
# Script to export remaining data from Certificate Intership.accdb to MySQL format
# ==============================================================================
$ErrorActionPreference = "Stop"

$workspaceDir = "D:\Abubakar\AWT Internship Managment"
$dbPath = Join-Path $workspaceDir "Certificate Intership.accdb"
$outIncSql = Join-Path $workspaceDir "database\migrate_certificate_data.sql"
$masterFullSql = Join-Path $workspaceDir "database\awt_internship_full.sql"
$schemaSql = Join-Path $workspaceDir "database\schema.sql"
$seedSql = Join-Path $workspaceDir "database\seed_data.sql"
$migrateOldSql = Join-Path $workspaceDir "database\migrate_access_data.sql"

Write-Host "Opening Access DB: $dbPath"
$connStr = "Driver={Microsoft Access Driver (*.mdb, *.accdb)};DBQ=$dbPath;"
$conn = New-Object System.Data.Odbc.OdbcConnection($connStr)
$conn.Open()

$cmd = $conn.CreateCommand()
$cmd.CommandText = "SELECT * FROM [Student] ORDER BY id ASC"
$adapter = New-Object System.Data.Odbc.OdbcDataAdapter($cmd)
$dt = New-Object System.Data.DataTable
[void]$adapter.Fill($dt)
$conn.Close()

Write-Host "Loaded $($dt.Rows.Count) rows from Certificate Intership.accdb 'Student'."

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
    $escaped = $s.Replace("\", "\\").Replace("'", "''").Replace("`r`n", ", ").Replace("`n", ", ").Replace("`r", " ")
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

$sbInc = New-Object System.Text.StringBuilder
[void]$sbInc.AppendLine("-- =======================================================")
[void]$sbInc.AppendLine("-- AWT Intern Management System (AWT-IMS)")
[void]$sbInc.AppendLine("-- Incremental Data Import from 'Certificate Intership.accdb'")
[void]$sbInc.AppendLine("-- Generated on: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')")
[void]$sbInc.AppendLine("-- =======================================================")
[void]$sbInc.AppendLine("")
[void]$sbInc.AppendLine("SET FOREIGN_KEY_CHECKS = 0;")
[void]$sbInc.AppendLine("")

[void]$sbInc.AppendLine("-- -------------------------------------------------------")
[void]$sbInc.AppendLine("-- 1. Update Existing Intern Records with Certificate Data")
[void]$sbInc.AppendLine("-- -------------------------------------------------------")

# Updates for the 3 existing students:
# ID 860: Alina Javed
[void]$sbInc.AppendLine("UPDATE `interns` SET ")
[void]$sbInc.AppendLine("    `Certificate_Period_Detail` = '25th July 2024 to 24th Aug 2024',")
[void]$sbInc.AppendLine("    `Hours` = '1 Month',")
[void]$sbInc.AppendLine("    `iyear` = '2024',")
[void]$sbInc.AppendLine("    `confirmed` = 1,")
[void]$sbInc.AppendLine("    `status` = 'confirmed'")
[void]$sbInc.AppendLine("WHERE `id` = 860;")
[void]$sbInc.AppendLine("")

# ID 863: Ammar Ahmed Shaikh
[void]$sbInc.AppendLine("UPDATE `interns` SET ")
[void]$sbInc.AppendLine("    `Certificate_Period_Detail` = '01 August 2024 to 12 September 2024',")
[void]$sbInc.AppendLine("    `Hours` = '06 Weeks',")
[void]$sbInc.AppendLine("    `iyear` = '2024',")
[void]$sbInc.AppendLine("    `confirmed` = 1,")
[void]$sbInc.AppendLine("    `status` = 'confirmed'")
[void]$sbInc.AppendLine("WHERE `id` = 863;")
[void]$sbInc.AppendLine("")

# ID 866: Nabeel Afaq Chandna
[void]$sbInc.AppendLine("UPDATE `interns` SET ")
[void]$sbInc.AppendLine("    `Degree` = 'economics and mathematics',")
[void]$sbInc.AppendLine("    `CV` = 1,")
[void]$sbInc.AppendLine("    `CNIC_copy` = 1,")
[void]$sbInc.AppendLine("    `Student_ID` = 1")
[void]$sbInc.AppendLine("WHERE `id` = 866;")
[void]$sbInc.AppendLine("")

[void]$sbInc.AppendLine("-- -------------------------------------------------------")
[void]$sbInc.AppendLine("-- 2. Insert 64 New Authentic Intern Records")
[void]$sbInc.AppendLine("-- -------------------------------------------------------")

# Keep track of certifiable interns for certificates table insertion
$certifiedInterns = @()

# Also include the 2 updated interns in certificates ledger if desired
$certifiedInterns += [PSCustomObject]@{
    InternId = 860
    CertNo = "AWT-2024-0860"
    IssueDate = "2024-08-24"
    Period = "25th July 2024 to 24th Aug 2024"
    Hours = "1 Month"
}
$certifiedInterns += [PSCustomObject]@{
    InternId = 863
    CertNo = "AWT-2024-0863"
    IssueDate = "2024-09-12"
    Period = "01 August 2024 to 12 September 2024"
    Hours = "06 Weeks"
}

$salimHabibNextId = 917
$newInternSqlLines = @()

foreach ($r in $dt.Rows) {
    $origId = [int]$r["id"]
    
    # Skip records that merged with existing 860, 863, 866
    if ($origId -eq 873) { continue } # Alina Javed -> 860
    if ($origId -eq 875) { continue } # Ammar Ahmed Shaikh -> 863
    if ($origId -eq 895) { continue } # Nabeel Afaq -> 866

    $newId = $origId
    if ($origId -ge 850 -and $origId -le 866) {
        # Salim Habib University students remapped from 850-866 to 917-933
        $newId = $salimHabibNextId
        $salimHabibNextId++
    }

    $vals = @()
    $vals += $newId.ToString()
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

    $periodVal = "$($r['Certificate Period Detail'])".Trim()
    $hasCertDetail = ($periodVal -ne "" -and $periodVal -ne [DBNull]::Value)

    $isConfirmed = [bool]$r["confirmed"]
    $isWaiting = [bool]$r["Waiting"]

    # If intern has certificate details, they are completed and confirmed
    if ($hasCertDetail) {
        $isConfirmed = $true
    }

    $vals += Escape-Sql $isConfirmed
    $vals += Escape-Sql $isWaiting

    # Compute status
    $status = "pending"
    $punc = 0
    if ($r["punctuality"] -ne [DBNull]::Value) { $punc = [int]$r["punctuality"] }

    if ($hasCertDetail -or $punc -gt 0) {
        $status = "completed"
    } elseif ($isConfirmed) {
        $status = "confirmed"
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
    $vals += "NULL" # ERPNO

    $line = "INSERT INTO `interns` (" + ($columns -join ", ") + ") VALUES (" + ($vals -join ", ") + ");"
    [void]$sbInc.AppendLine($line)
    $newInternSqlLines += $line

    # Check if this intern has a certificate period detail
    $periodVal = "$($r['Certificate Period Detail'])".Trim()
    if ($periodVal -ne "" -and $periodVal -ne [DBNull]::Value) {
        $yr = "$($r['iyear'])".Trim()
        if ($yr -eq "") { $yr = "2021" }
        $padId = $newId.ToString().PadLeft(4, '0')
        $certNo = "AWT-$yr-$padId"
        
        # Approximate issue date from entry date or period
        $issueDate = "2021-10-30"
        if ($r["Dateofentry"] -ne [DBNull]::Value) {
            $issueDate = ([DateTime]$r["Dateofentry"]).ToString("yyyy-MM-dd")
        }

        $certifiedInterns += [PSCustomObject]@{
            InternId = $newId
            CertNo = $certNo
            IssueDate = $issueDate
            Period = $periodVal
            Hours = "$($r['Hours'])".Trim()
        }
    }
}

[void]$sbInc.AppendLine("")
[void]$sbInc.AppendLine("-- -------------------------------------------------------")
[void]$sbInc.AppendLine("-- 3. Pre-populate Certificates Ledger for Certified Interns")
[void]$sbInc.AppendLine("-- -------------------------------------------------------")

$rng = New-Object System.Random(12345)
foreach ($ci in $certifiedInterns) {
    # Generate deterministic 32-hex token for verification QR
    $hashBytes = [System.Security.Cryptography.SHA256]::Create().ComputeHash([System.Text.Encoding]::UTF8.GetBytes("$($ci.CertNo)-AWT-CERT-SALT"))
    $qrToken = -join ($hashBytes[0..15] | ForEach-Object { $_.ToString("x2") })
    
    $cLine = "INSERT INTO `certificates` (`intern_id`, `certificate_no`, `issue_date`, `period_detail`, `hours_duration`, `qr_token`, `status`) " +
             "VALUES ($($ci.InternId), '$($ci.CertNo)', '$($ci.IssueDate)', $(Escape-Sql $ci.Period), $(Escape-Sql $ci.Hours), '$qrToken', 'Issued') " +
             "ON DUPLICATE KEY UPDATE `period_detail` = VALUES(`period_detail`), `hours_duration` = VALUES(`hours_duration`);"
    [void]$sbInc.AppendLine($cLine)
}

[void]$sbInc.AppendLine("")
[void]$sbInc.AppendLine("SET FOREIGN_KEY_CHECKS = 1;")
[void]$sbInc.AppendLine("-- Successfully prepared $($newInternSqlLines.Count) new intern inserts, 3 updates, and $($certifiedInterns.Count) certificate records.")

[System.IO.File]::WriteAllText($outIncSql, $sbInc.ToString(), [System.Text.Encoding]::UTF8)
Write-Host "Created incremental migration: $outIncSql"
Write-Host "Total new interns: $($newInternSqlLines.Count), Certified interns: $($certifiedInterns.Count)"

# ==============================================================================
# Update awt_internship_full.sql to consolidate all 764 interns
# ==============================================================================
Write-Host "Regenerating master awt_internship_full.sql..."
$fullSb = New-Object System.Text.StringBuilder

# 1. Schema
$schemaContent = [System.IO.File]::ReadAllText($schemaSql, [System.Text.Encoding]::UTF8)
[void]$fullSb.AppendLine($schemaContent)
[void]$fullSb.AppendLine("")

# 2. Seed Data
$seedContent = [System.IO.File]::ReadAllText($seedSql, [System.Text.Encoding]::UTF8)
[void]$fullSb.AppendLine($seedContent)
[void]$fullSb.AppendLine("")

# 3. Base 700 Intern Records from New Internship_be.accdb
$baseMigrateContent = [System.IO.File]::ReadAllText($migrateOldSql, [System.Text.Encoding]::UTF8)
# Extract only INSERT statements from migrate_access_data.sql
$baseLines = $baseMigrateContent -split "`r?`n"
[void]$fullSb.AppendLine("-- =======================================================")
[void]$fullSb.AppendLine("-- Part 3A: 700 Legacy Interns (from New Internship_be.accdb)")
[void]$fullSb.AppendLine("-- =======================================================")
[void]$fullSb.AppendLine("SET FOREIGN_KEY_CHECKS = 0;")
foreach ($bl in $baseLines) {
    if ($bl -match "^INSERT INTO `?interns`?") {
        [void]$fullSb.AppendLine($bl)
    }
}

# 4. Updates for 860, 863, 866
[void]$fullSb.AppendLine("")
[void]$fullSb.AppendLine("-- =======================================================")
[void]$fullSb.AppendLine("-- Part 3B: Updates from Certificate Intership.accdb")
[void]$fullSb.AppendLine("-- =======================================================")
[void]$fullSb.AppendLine("UPDATE `interns` SET `Certificate_Period_Detail` = '25th July 2024 to 24th Aug 2024', `Hours` = '1 Month', `iyear` = '2024', `confirmed` = 1, `status` = 'confirmed' WHERE `id` = 860;")
[void]$fullSb.AppendLine("UPDATE `interns` SET `Certificate_Period_Detail` = '01 August 2024 to 12 September 2024', `Hours` = '06 Weeks', `iyear` = '2024', `confirmed` = 1, `status` = 'confirmed' WHERE `id` = 863;")
[void]$fullSb.AppendLine("UPDATE `interns` SET `Degree` = 'economics and mathematics', `CV` = 1, `CNIC_copy` = 1, `Student_ID` = 1 WHERE `id` = 866;")

# 5. The 64 new records
[void]$fullSb.AppendLine("")
[void]$fullSb.AppendLine("-- =======================================================")
[void]$fullSb.AppendLine("-- Part 3C: 64 New Interns (from Certificate Intership.accdb)")
[void]$fullSb.AppendLine("-- Total Interns in System: 764")
[void]$fullSb.AppendLine("-- =======================================================")
foreach ($nl in $newInternSqlLines) {
    [void]$fullSb.AppendLine($nl)
}

# 6. Certificates
[void]$fullSb.AppendLine("")
[void]$fullSb.AppendLine("-- =======================================================")
[void]$fullSb.AppendLine("-- Part 4: Issued Certificates for Certified Interns")
[void]$fullSb.AppendLine("-- =======================================================")
foreach ($ci in $certifiedInterns) {
    $hashBytes = [System.Security.Cryptography.SHA256]::Create().ComputeHash([System.Text.Encoding]::UTF8.GetBytes("$($ci.CertNo)-AWT-CERT-SALT"))
    $qrToken = -join ($hashBytes[0..15] | ForEach-Object { $_.ToString("x2") })
    $cLine = "INSERT INTO `certificates` (`intern_id`, `certificate_no`, `issue_date`, `period_detail`, `hours_duration`, `qr_token`, `status`) " +
             "VALUES ($($ci.InternId), '$($ci.CertNo)', '$($ci.IssueDate)', $(Escape-Sql $ci.Period), $(Escape-Sql $ci.Hours), '$qrToken', 'Issued');"
    [void]$fullSb.AppendLine($cLine)
}

[void]$fullSb.AppendLine("")
[void]$fullSb.AppendLine("SET FOREIGN_KEY_CHECKS = 1;")
[void]$fullSb.AppendLine("-- Master Full Dump Completed: 764 Interns, Schema, Seed Data, Certificates.")

[System.IO.File]::WriteAllText($masterFullSql, $fullSb.ToString(), [System.Text.Encoding]::UTF8)
Write-Host "Updated master all-in-one SQL: $masterFullSql"
