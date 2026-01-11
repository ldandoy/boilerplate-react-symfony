import { useState } from "react";

export const Counter = () => {
  const [age, setAge] = useState(0);
  console.log("test render");

  return (
    <div className="mx-auto">
      <p>{age}</p>
      <button
        className="btn bg-amber-600 dark:bg-amber-900 rounded-2xl py-2 px-4"
        onClick={() => setAge(age + 1)}
      >
        Increment
      </button>
    </div>
  );
};
