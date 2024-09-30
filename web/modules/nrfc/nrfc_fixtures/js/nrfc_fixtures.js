document.addEventListener("DOMContentLoaded", function () {
  const target = document.getElementById('spreadsheet');
  // https://bossanova.uk/jspreadsheet/v4/
  jspreadsheet(target, {
    data: drupalSettings.nrfc_fixtures.rows,
    columns: [
      {
        type: 'text',
        title: 'Car',
        width: 90
      },
      {
        type: 'dropdown',
        title: 'Make',
        width: 120,
        source: [
          "Home",
          "Away",
        ]
      },
    ]
  });
});
