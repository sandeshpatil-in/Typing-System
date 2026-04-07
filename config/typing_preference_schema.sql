CREATE TABLE IF NOT EXISTS languages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS exam_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    language_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    wpm INT NOT NULL,
    time_limit INT NOT NULL,
    CONSTRAINT fk_exam_types_language FOREIGN KEY (language_id) REFERENCES languages(id) ON DELETE CASCADE,
    UNIQUE KEY uq_exam_type_language_name (language_id, name)
);

CREATE TABLE IF NOT EXISTS passages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    language_id INT NOT NULL,
    exam_type_id INT NOT NULL,
    content TEXT NOT NULL,
    CONSTRAINT fk_passages_language FOREIGN KEY (language_id) REFERENCES languages(id) ON DELETE CASCADE,
    CONSTRAINT fk_passages_exam_type FOREIGN KEY (exam_type_id) REFERENCES exam_types(id) ON DELETE CASCADE
);

INSERT INTO languages (name)
VALUES ('English'), ('Marathi'), ('Hindi')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO exam_types (language_id, name, wpm, time_limit)
SELECT l.id, 'Skill Test', 30, 300 FROM languages l
ON DUPLICATE KEY UPDATE wpm = VALUES(wpm), time_limit = VALUES(time_limit);

INSERT INTO exam_types (language_id, name, wpm, time_limit)
SELECT l.id, 'Intermediate', 40, 300 FROM languages l
ON DUPLICATE KEY UPDATE wpm = VALUES(wpm), time_limit = VALUES(time_limit);

INSERT INTO exam_types (language_id, name, wpm, time_limit)
SELECT l.id, 'Expert', 50, 300 FROM languages l
ON DUPLICATE KEY UPDATE wpm = VALUES(wpm), time_limit = VALUES(time_limit);

INSERT INTO passages (language_id, exam_type_id, content)
SELECT l.id, e.id, 'The quick brown fox jumps over the lazy dog. Practice consistently to improve your English typing speed and accuracy.'
FROM languages l
JOIN exam_types e ON e.language_id = l.id
WHERE l.name = 'English' AND e.name = 'Skill Test'
AND NOT EXISTS (
    SELECT 1 FROM passages p WHERE p.language_id = l.id AND p.exam_type_id = e.id
);

INSERT INTO passages (language_id, exam_type_id, content)
SELECT l.id, e.id, 'Typing tests help candidates build confidence, maintain rhythm, and reach the required speed for competitive exams.'
FROM languages l
JOIN exam_types e ON e.language_id = l.id
WHERE l.name = 'English' AND e.name = 'Intermediate'
AND NOT EXISTS (
    SELECT 1 FROM passages p WHERE p.language_id = l.id AND p.exam_type_id = e.id
);

INSERT INTO passages (language_id, exam_type_id, content)
SELECT l.id, e.id, 'Advanced typing practice improves consistency, control, and speed under strict time pressure for expert-level tests.'
FROM languages l
JOIN exam_types e ON e.language_id = l.id
WHERE l.name = 'English' AND e.name = 'Expert'
AND NOT EXISTS (
    SELECT 1 FROM passages p WHERE p.language_id = l.id AND p.exam_type_id = e.id
);

INSERT INTO passages (language_id, exam_type_id, content)
SELECT l.id, e.id, 'मराठी टायपिंगचा सराव नियमित केल्यास गती, अचूकता आणि परीक्षेतील आत्मविश्वास दोन्ही वाढतात.'
FROM languages l
JOIN exam_types e ON e.language_id = l.id
WHERE l.name = 'Marathi' AND e.name = 'Skill Test'
AND NOT EXISTS (
    SELECT 1 FROM passages p WHERE p.language_id = l.id AND p.exam_type_id = e.id
);

INSERT INTO passages (language_id, exam_type_id, content)
SELECT l.id, e.id, 'मध्यम स्तरावरील सराव विद्यार्थ्यांना वेळेच्या मर्यादेत अचूक टायपिंग करण्याची सवय लावतो.'
FROM languages l
JOIN exam_types e ON e.language_id = l.id
WHERE l.name = 'Marathi' AND e.name = 'Intermediate'
AND NOT EXISTS (
    SELECT 1 FROM passages p WHERE p.language_id = l.id AND p.exam_type_id = e.id
);

INSERT INTO passages (language_id, exam_type_id, content)
SELECT l.id, e.id, 'उच्च स्तरावरील टायपिंगमध्ये गतीसोबत अचूकता टिकवणे तितकेच महत्त्वाचे असते.'
FROM languages l
JOIN exam_types e ON e.language_id = l.id
WHERE l.name = 'Marathi' AND e.name = 'Expert'
AND NOT EXISTS (
    SELECT 1 FROM passages p WHERE p.language_id = l.id AND p.exam_type_id = e.id
);

INSERT INTO passages (language_id, exam_type_id, content)
SELECT l.id, e.id, 'हिंदी टाइपिंग का अभ्यास लगातार करने से गति और शुद्धता दोनों बेहतर होती हैं।'
FROM languages l
JOIN exam_types e ON e.language_id = l.id
WHERE l.name = 'Hindi' AND e.name = 'Skill Test'
AND NOT EXISTS (
    SELECT 1 FROM passages p WHERE p.language_id = l.id AND p.exam_type_id = e.id
);

INSERT INTO passages (language_id, exam_type_id, content)
SELECT l.id, e.id, 'मध्यम स्तर का अभ्यास उम्मीदवार को समय सीमा में स्थिर और साफ टाइपिंग करने में मदद करता है।'
FROM languages l
JOIN exam_types e ON e.language_id = l.id
WHERE l.name = 'Hindi' AND e.name = 'Intermediate'
AND NOT EXISTS (
    SELECT 1 FROM passages p WHERE p.language_id = l.id AND p.exam_type_id = e.id
);

INSERT INTO passages (language_id, exam_type_id, content)
SELECT l.id, e.id, 'विशेषज्ञ स्तर की टाइपिंग में तेज गति के साथ लगातार सही आउटपुट देना सबसे बड़ी चुनौती होती है।'
FROM languages l
JOIN exam_types e ON e.language_id = l.id
WHERE l.name = 'Hindi' AND e.name = 'Expert'
AND NOT EXISTS (
    SELECT 1 FROM passages p WHERE p.language_id = l.id AND p.exam_type_id = e.id
);
