<!DOCTYPE html>
<html>
<head>
    <title>BICHAMCO Loan Application</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        h2, h3 { background: #eee; padding: 10px; }
        input, select, textarea { width: 250px; padding: 5px; margin: 5px 0; }
        table { border-collapse: collapse; width: 100%; margin-top: 10px; }
        table, th, td { border: 1px solid black; padding: 5px; }
    </style>
</head>
<body>

<h2>BICHAMCO LOAN APPLICATION FORM</h2>

<form method="POST" action="process.php">

<!-- APPLICANT -->
<h3>Applicant Information</h3>

Name: <input type="text" name="name"><br>
Date of Birth: <input type="date" name="dob"><br>
Age: <input type="number" name="age"><br>
Sex: 
<select name="sex">
<option>Male</option>
<option>Female</option>
</select><br>

Civil Status:
<select name="civil_status">
<option>Single</option>
<option>Married</option>
<option>Widow</option>
</select><br>

Residence Cert No: <input type="text" name="res_cert"><br>
Phone: <input type="text" name="phone"><br>
Occupation: <input type="text" name="occupation"><br>

Address: <input type="text" name="address"><br>

Residence Type:
<input type="checkbox" name="residence[]" value="owned"> Owned
<input type="checkbox" name="residence[]" value="mortgage"> Mortgage
<input type="checkbox" name="residence[]" value="rented"> Rented
<input type="checkbox" name="residence[]" value="free"> Free
<input type="checkbox" name="residence[]" value="parents"> With Parents

<!-- SPOUSE -->
<h3>Spouse</h3>
Name: <input type="text" name="spouse"><br>
DOB: <input type="date" name="spouse_dob"><br>
Occupation: <input type="text" name="spouse_job"><br>

<!-- BUSINESS -->
<h3>Business</h3>
Business Name: <input type="text" name="business"><br>
Facebook: <input type="text" name="facebook"><br>

<!-- BENEFICIARY -->
<h3>Beneficiary</h3>
Name: <input type="text" name="beneficiary"><br>
DOB: <input type="date" name="ben_dob"><br>
Sex: <input type="text" name="ben_sex"><br>
Relationship: <input type="text" name="relationship"><br>

<!-- DEPENDENTS -->
<h3>Dependents</h3>
<table>
<tr>
<th>Name</th>
<th>DOB</th>
<th>Age</th>
<th>Relationship</th>
</tr>

<tr>
<td><input type="text" name="dep_name[]"></td>
<td><input type="date" name="dep_dob[]"></td>
<td><input type="number" name="dep_age[]"></td>
<td><input type="text" name="dep_rel[]"></td>
</tr>

<tr>
<td><input type="text" name="dep_name[]"></td>
<td><input type="date" name="dep_dob[]"></td>
<td><input type="number" name="dep_age[]"></td>
<td><input type="text" name="dep_rel[]"></td>
</tr>
</table>

<!-- LOAN -->
<h3>Loan Details</h3>

<input type="checkbox" name="loan_type[]" value="regular"> Regular
<input type="checkbox" name="loan_type[]" value="salary"> Salary
<input type="checkbox" name="loan_type[]" value="micro"> Micro
<input type="checkbox" name="loan_type[]" value="pensioner"> Pensioner
<input type="checkbox" name="loan_type[]" value="special"> Special
<input type="checkbox" name="loan_type[]" value="agri"> Agricultural
Others: <input type="text" name="others"><br>

Amount: <input type="number" name="loan_amount"><br>
Rate (%): <input type="number" name="rate"><br>
Term: <input type="text" name="term"><br>
Mode: <input type="text" name="mode"><br>

<!-- INCOME -->
<h3>Income & Expenses</h3>

Gross Income: <input type="number" name="gross"><br>
Expenses: <input type="number" name="expenses"><br>
Net Income: <input type="number" name="net"><br>

<!-- OBLIGATIONS -->
<h3>Outstanding Loans</h3>

<table>
<tr>
<th>Creditor</th>
<th>Address</th>
<th>Amount</th>
<th>Due Date</th>
</tr>

<tr>
<td><input type="text" name="creditor[]"></td>
<td><input type="text" name="cred_addr[]"></td>
<td><input type="number" name="cred_amount[]"></td>
<td><input type="date" name="cred_due[]"></td>
</tr>

</table>

<!-- DECLARATION -->
<h3>Declaration</h3>

<textarea name="declaration">
I certify that all information is true.
</textarea><br>

Signature: <input type="text" name="signature"><br>

<!-- ADMIN SECTION -->
<h3>FOR OFFICE USE ONLY</h3>

Approved <input type="checkbox" name="status" value="approved">
Deferred <input type="checkbox" name="status" value="deferred">
BOD Decision <input type="checkbox" name="status" value="bod"><br>

Remarks:<br>
<textarea name="remarks"></textarea><br>

Manager: <input type="text" name="manager"><br>

<!-- SUBMIT -->
<br><br>
<input type="submit" value="Submit">

</form>

</body>
</html>