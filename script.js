const timeSlots = [
  "9:00-10:00",
  "10:00-11:00",
  "11:00-12:00",
  "12:00-1:00",
  "1:00-2:00",
  "2:00-3:00",
  "3:00-4:00",
  "4:00-5:00",
  "5:00-6:00"
];

const timetable = {
  Monday: ["AE", "DS", "MTTDE", "APG", "Break", "", "AP (A2) Lab 2 / DS (A1) Lab 6", "AP (A2) Lab 2 / DS (A1) Lab 6", ""],
  Tuesday: ["AE", "AE", "APG", "DS", "Break", "", "AP (A1) Lab 8", "AP (A1) Lab 8", ""],
  Wednesday: ["GDDT", "AP", "MTTDE", "APG", "Break", "AE (A1) Electronics Lab", "AE (A1) Electronics Lab", "AE (A2) Electronics Lab", "AE (A2) Electronics Lab"],
  Thursday: ["GDDT", "AP", "MTTDE", "MTTDE Tute-1", "Break", "EVS (CR-302)", "", "", ""],
  Friday: ["MTTDE Tute-2", "AP", "EVS", "DS", "Break", "", "DS (A2) Lab 2", "DS (A2) Lab 2", ""]
};

const subjectNames = ["AE", "DS", "MTTDE", "APG", "AP", "GDDT", "EVS"];
const days = Object.keys(timetable);
let attendance = JSON.parse(localStorage.getItem("smartAttendance")) || {};

const daySelect = document.getElementById("daySelect");
const datePicker = document.getElementById("datePicker");
const classList = document.getElementById("classList");
const timeTable = document.getElementById("timeTable");
const subjectGrid = document.getElementById("subjectGrid");
const calendarGrid = document.getElementById("calendarGrid");
let calendarDate = new Date();

function formatDate(date) {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

function getSelectedDate() {
  return datePicker.value;
}

function getSubject(className) {
  return subjectNames.find(subject => className.startsWith(subject)) || "";
}

function getClassesForDay(day) {
  if (!timetable[day]) return [];

  return timetable[day]
    .map((name, index) => ({ name, time: timeSlots[index], index }))
    .filter(item => item.name && item.name !== "Break");
}

function getClassId(date, day, item) {
  return `${date}-${day}-${item.index}-${item.name}`;
}

function saveAttendance() {
  localStorage.setItem("smartAttendance", JSON.stringify(attendance));
}

function markAttendance(day, item, value) {
  const date = getSelectedDate();

  attendance[getClassId(date, day, item)] = {
    subject: getSubject(item.name),
    className: item.name,
    date,
    day,
    time: item.time,
    present: value
  };

  saveAttendance();
  renderAll();
}

function calculateStats() {
  const stats = {};

  subjectNames.forEach(subject => {
    stats[subject] = { present: 0, total: 0 };
  });

  Object.values(attendance).forEach(record => {
    if (!record.subject || !record.date) return;
    stats[record.subject].total++;
    if (record.present) stats[record.subject].present++;
  });

  return stats;
}

function getPercent(present, total) {
  if (total === 0) return 0;
  return Math.round((present / total) * 100);
}

function classesNeededFor75(present, total) {
  let needed = 0;

  while (total > 0 && ((present + needed) / (total + needed)) < 0.75) {
    needed++;
  }

  return needed;
}

function renderDate() {
  const today = new Date();
  const dateText = today.toLocaleDateString("en-IN", {
    day: "numeric",
    month: "short",
    year: "numeric"
  });

  document.getElementById("currentDay").textContent = today.toLocaleDateString("en-IN", { weekday: "long" });
  document.getElementById("currentDate").textContent = dateText;
}

function renderDaySelect() {
  daySelect.innerHTML = [`<option value="">No classes</option>`]
    .concat(days.map(day => `<option value="${day}">${day}</option>`))
    .join("");
}

function renderClassList() {
  const selectedDay = daySelect.value;
  const selectedDate = getSelectedDate();
  const classes = getClassesForDay(selectedDay);

  if (!selectedDate) {
    classList.innerHTML = `<p class="empty-note">Choose a date first.</p>`;
    return;
  }

  if (classes.length === 0) {
    classList.innerHTML = `<p class="empty-note">No classes for this day.</p>`;
    return;
  }

  classList.innerHTML = classes.map(item => {
    const classId = getClassId(selectedDate, selectedDay, item);
    const saved = attendance[classId];
    const presentClass = saved?.present === true ? "" : "inactive";
    const absentClass = saved?.present === false ? "" : "inactive";

    return `
      <div class="class-item">
        <div class="class-info">
          <strong>${item.name}</strong>
          <span>${item.time}</span>
        </div>
        <div class="mark-buttons">
          <button class="present-btn ${presentClass}" data-id="${classId}" data-status="present">Present</button>
          <button class="absent-btn ${absentClass}" data-id="${classId}" data-status="absent">Absent</button>
        </div>
      </div>
    `;
  }).join("");
}

function renderTable() {
  const heading = ["Day", ...timeSlots].map(slot => `<th>${slot}</th>`).join("");

  const rows = days.map(day => {
    const cells = timetable[day].map(className => {
      let classType = className ? "has-class" : "";
      if (className === "Break") classType = "break-cell";
      if (className.includes("Lab")) classType = "lab-cell";
      return `<td class="${classType}">${className}</td>`;
    }).join("");

    return `<tr><td class="day-name">${day}</td>${cells}</tr>`;
  }).join("");

  timeTable.innerHTML = `<thead><tr>${heading}</tr></thead><tbody>${rows}</tbody>`;
}

function renderCalendar() {
  const year = calendarDate.getFullYear();
  const month = calendarDate.getMonth();
  const firstDay = new Date(year, month, 1).getDay();
  const totalDays = new Date(year, month + 1, 0).getDate();
  const selectedDate = getSelectedDate();

  document.getElementById("monthTitle").textContent =
    calendarDate.toLocaleDateString("en-IN", { month: "long", year: "numeric" });

  let boxes = "";

  for (let blank = 0; blank < firstDay; blank++) {
    boxes += `<button class="calendar-day empty" type="button" disabled></button>`;
  }

  for (let day = 1; day <= totalDays; day++) {
    const dateText = formatDate(new Date(year, month, day));
    const hasRecord = Object.values(attendance).some(record => record.date === dateText);
    const selectedClass = dateText === selectedDate ? "selected" : "";
    const markedClass = hasRecord ? "marked" : "";

    boxes += `<button class="calendar-day ${selectedClass} ${markedClass}" type="button" data-date="${dateText}">${day}</button>`;
  }

  calendarGrid.innerHTML = boxes;
}

function renderSummary(stats) {
  const totals = Object.values(stats).reduce((sum, item) => {
    sum.present += item.present;
    sum.total += item.total;
    return sum;
  }, { present: 0, total: 0 });

  const percent = getPercent(totals.present, totals.total);
  const lowSubjects = Object.values(stats)
    .filter(item => item.total > 0 && getPercent(item.present, item.total) < 75)
    .length;
  const overallCard = document.querySelector(".summary-card");

  document.getElementById("overallPercent").textContent = `${percent}%`;
  document.getElementById("overallCount").textContent = `${totals.present} / ${totals.total}`;
  document.getElementById("needCount").textContent = lowSubjects;

  overallCard.classList.toggle("danger", totals.total > 0 && percent < 75);
  overallCard.classList.toggle("safe", percent >= 75 || totals.total === 0);

  document.getElementById("overallMessage").textContent =
    totals.total === 0 ? "Start marking classes." :
    percent >= 75 ? "Great, you are above 75%." :
    "Warning, attendance is below 75%.";

  document.getElementById("needMessage").textContent =
    lowSubjects === 0 ? "Every marked subject is safe." : "Check priority subject below.";
}

function renderInsights(stats) {
  const markedSubjects = subjectNames
    .map(subject => ({
      name: subject,
      present: stats[subject].present,
      total: stats[subject].total,
      percent: getPercent(stats[subject].present, stats[subject].total),
      need: classesNeededFor75(stats[subject].present, stats[subject].total)
    }))
    .filter(subject => subject.total > 0);

  if (markedSubjects.length === 0) {
    document.getElementById("prioritySubject").textContent = "Start marking";
    document.getElementById("priorityMessage").textContent = "The weakest subject will appear here.";
    document.getElementById("bestSubject").textContent = "Start marking";
    document.getElementById("bestMessage").textContent = "Your strongest subject will appear here.";
    return;
  }

  const priority = markedSubjects.reduce((lowest, subject) =>
    subject.percent < lowest.percent ? subject : lowest
  );

  const best = markedSubjects.reduce((highest, subject) =>
    subject.percent > highest.percent ? subject : highest
  );

  document.getElementById("prioritySubject").textContent = `${priority.name} - ${priority.percent}%`;
  document.getElementById("priorityMessage").textContent =
    priority.percent >= 75
      ? "This is your lowest marked subject, but it is still safe."
      : `Attend ${priority.need} more class(es) in ${priority.name} to reach 75%.`;

  document.getElementById("bestSubject").textContent = `${best.name} - ${best.percent}%`;
  document.getElementById("bestMessage").textContent =
    `${best.present}/${best.total} classes marked present in this subject.`;
}

function renderSubjects(stats) {
  subjectGrid.innerHTML = subjectNames.map(subject => {
    const item = stats[subject];
    const percent = getPercent(item.present, item.total);
    const need = classesNeededFor75(item.present, item.total);
    const statusClass = percent >= 75 || item.total === 0 ? "ok" : "low";
    const statusText = percent >= 75 || item.total === 0 ? "Safe" : "Low";

    return `
      <article class="subject-card">
        <div class="subject-top">
          <strong>${subject}</strong>
          <span class="status ${statusClass}">${statusText}</span>
        </div>
        <div class="progress"><span style="width: ${percent}%"></span></div>
        <p>${percent}% attendance - ${item.present}/${item.total} classes marked.</p>
        <p>${need === 0 ? "You can maintain 75%." : `Attend ${need} more class(es) to reach 75%.`}</p>
      </article>
    `;
  }).join("");
}

function renderAll() {
  const stats = calculateStats();
  renderClassList();
  renderTable();
  renderCalendar();
  renderSummary(stats);
  renderInsights(stats);
  renderSubjects(stats);
}

function setDayFromDate() {
  const selected = new Date(`${getSelectedDate()}T00:00:00`);
  const dayName = selected.toLocaleDateString("en-IN", { weekday: "long" });

  daySelect.value = days.includes(dayName) ? dayName : "";
}

datePicker.addEventListener("change", () => {
  calendarDate = new Date(`${getSelectedDate()}T00:00:00`);
  setDayFromDate();
  renderAll();
});

classList.addEventListener("click", event => {
  if (event.target.tagName !== "BUTTON") return;

  const selectedDay = daySelect.value;
  const selectedDate = getSelectedDate();
  const classes = getClassesForDay(selectedDay);
  const clickedClass = classes.find(item => getClassId(selectedDate, selectedDay, item) === event.target.dataset.id);

  if (clickedClass) {
    markAttendance(selectedDay, clickedClass, event.target.dataset.status === "present");
  }
});

calendarGrid.addEventListener("click", event => {
  if (!event.target.dataset.date) return;

  datePicker.value = event.target.dataset.date;
  calendarDate = new Date(`${datePicker.value}T00:00:00`);
  setDayFromDate();
  renderAll();
});

document.getElementById("prevMonth").addEventListener("click", () => {
  calendarDate.setMonth(calendarDate.getMonth() - 1);
  renderCalendar();
});

document.getElementById("nextMonth").addEventListener("click", () => {
  calendarDate.setMonth(calendarDate.getMonth() + 1);
  renderCalendar();
});

document.getElementById("resetBtn").addEventListener("click", () => {
  if (confirm("Reset all attendance data?")) {
    attendance = {};
    saveAttendance();
    renderAll();
  }
});

datePicker.value = formatDate(new Date());
calendarDate = new Date(`${datePicker.value}T00:00:00`);
renderDate();
renderDaySelect();
setDayFromDate();
renderAll();