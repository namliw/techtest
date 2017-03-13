# Invoice Generator

This code receives a basic CSV file with the following information
"first name","last name","annual salary","super rate (%)","payment start date"

and outputs a CSV file with the applied tax calculations
"name","pay period","gross income","income tax","net income","super"


## Assumptions

1. Tax brackets are always created in the order they need to be applied
2. Payment Start date is always a monthtly figure and doesnt affect the calculations

## Considerations

The idea behind this code is to implement a simple work queue for procesing what it could be a large CSV file coming from a large amount of clients. This could have been built using a simple straight forward script but for the sake of user friendliness and ease of use silverstripe CMS was implemented.

## How it works

Using the CMS an user is capable to create different financial years with "N" financial brackets.
After financial years are created the user can then create a "Payslip job" object associated with a particular year.
The Payslip job will contain the input file and is the entity that will be use by the work queue to load the information needed to generate the new output file with the amounts to invoice.

## How to run

1. You will need a lamp server to run the application
2. after extracting the files on the configured webroot, run
```
composer install
```
2. after composer is done installing visit your site and follow the installation instructions
3. visit the admin section of the CMS and follow the How to use section

## How to use

On a clean install you will need to create a new financial year in order to generate request to the work queue.
To do so, visit the "Payslip Admin" section on the CMS and on the top right corner press "Financial Year"
Once there click "Add Financial Year".

enter the new year that will contain the financial brackets and press save.
once the new financial year is saved the option to add financial brackets will appear.

Press on the "Financial Brackets" tab and start adding the brackets the will be used for the tax calculations.

Once you've added all the new brackets, return to the "payslip admin" main window and create and new "Payslip Job"

The first field you need to set is the financial year, select one from the dropdown and press save.

Once saved, you can now upload the CSV file to process or you can press "Generate test source file" for the CMS to generate one for you, the information there is randomly created using the [https://github.com/fzaninotto/Faker](faker) library, so you'll still see some readable information :)

Once the input file has been associated with the new job a button will appear "Generate invoice file" this will allow you to queue the current job for processing, once you press that button all editing capabilities will be disabled and will only be restored if the job ends in error. If everything goes well  you'll be presented with a link to download the final output file.

It normally takes around 1 min to generate the new file, if an error occurs visit the "Jobs" section on the CMS to check the error message.



