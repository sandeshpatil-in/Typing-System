INSERT INTO exam_types (language_id, name, wpm, time_limit)
SELECT l.id, 'Skill Test', 30, 300
FROM languages l
WHERE NOT EXISTS (
    SELECT 1
    FROM exam_types e
    WHERE e.language_id = l.id AND LOWER(e.name) = 'skill test'
);

INSERT INTO exam_types (language_id, name, wpm, time_limit)
SELECT l.id, 'Intermediate', 40, 300
FROM languages l
WHERE NOT EXISTS (
    SELECT 1
    FROM exam_types e
    WHERE e.language_id = l.id AND LOWER(e.name) = 'intermediate'
);

INSERT INTO exam_types (language_id, name, wpm, time_limit)
SELECT l.id, 'Expert', 50, 300
FROM languages l
WHERE NOT EXISTS (
    SELECT 1
    FROM exam_types e
    WHERE e.language_id = l.id AND LOWER(e.name) = 'expert'
);
