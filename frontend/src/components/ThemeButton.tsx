import { useTheme } from "../hooks/useTheme";
import { FaMoon, FaSun } from "react-icons/fa";

function ThemeButton() {
  const { theme, toggleTheme } = useTheme();

  return (
    <button onClick={toggleTheme}>
      {theme === "dark" ? <FaMoon /> : <FaSun />}
    </button>
  );
}

export { ThemeButton };
