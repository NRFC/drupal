document.addEventListener("DOMContentLoaded", function () {
  const target = document.getElementById('spreadsheet');
  // https://bossanova.uk/jspreadsheet/v4/
  jspreadsheet(target, {
    data: drupalSettings.nrfc_fixtures.rows,
    columns: [
      {
        type: 'calendar',
        title: 'Date',
        width: 90
      },
      {
        type: 'text',
        title: 'KO Time',
        width: 90
      },
      {
        type: 'dropdown',
        title: 'H/A',
        width: 120,
        source: [
          "Home",
          "Away",
        ]
      },
      {
        type: 'dropdown',
        title: 'Match type',
        width: 120,
        source: [
          "League",
          "Cup",
          "Friendly",
          "Nat Cup",
          "Cup (Other)",
        ]
      },
      {
        type: 'text',
        title: 'Opponent',
        width: 90
      },
      {
        type: 'text',
        title: 'Result',
        width: 90
      },
      {
        type: 'text',
        title: 'Report',
        width: 90
      },
      {
        type: 'checkbox',
        title: 'Referee',
        width: 90
      },
      {
        type: 'numeric',
        title: 'Food',
        width: 90
      },
      {
        type: 'text',
        title: 'Food notes',
        width: 90
      },
    ]
  });
});
